<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\EvaluateAttemptAnswer;
use App\Actions\FindOpenPrincipleSession;
use App\Actions\PrepareExercise;
use App\Actions\RecordPrincipleAttempt;
use App\DTO\PreparedExercise;
use App\DTO\SubmittedAnswer;
use App\Enums\AiVerdict;
use App\Enums\ExerciseType;
use App\Enums\SelfRating;
use App\Exceptions\InvalidExercisePayload;
use App\Support\Exercises\ExerciseStrategies;
use App\Support\Outcome;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

final readonly class SubmitExerciseAnswerOrchestrator
{
    private const MAX_ANSWER_LENGTH = 5000;

    public function __construct(
        private FindOpenPrincipleSession $findSession,
        private PrepareExercise $prepare,
        private RecordPrincipleAttempt $record,
        private ExerciseStrategies $strategies,
        private EvaluateAttemptAnswer $evaluate,
    ) {}

    /**
     * Devolve um SubmittedAnswer em `data`: a tentativa gravada (ainda sem autoavaliação) e,
     * se a IA não pôde avaliar, o aviso para mostrar ao usuário.
     */
    public function handle(
        int $accessTokenId,
        int $sessionId,
        int $index,
        ?string $answer,
        int $durationMs,
        ?CarbonImmutable $now = null,
        ?int $chosenPrincipleId = null,
    ): Outcome {
        try {
            $session = $this->findSession->handle($accessTokenId);
            $item = $session?->composition[$index] ?? null;

            if ($session === null || $session->id !== $sessionId || $item === null || $session->answered_count !== $index) {
                return Outcome::failure(message: 'Esta sessão mudou. Recarregue a página para continuar de onde parou.');
            }

            $type = ExerciseType::from($item['type']);
            $exerciseId = $item['exercise_id'] ?? null;

            try {
                $exercise = $this->prepare->handle($item['principle_id'], $type, $exerciseId);
            } catch (InvalidExercisePayload) {
                return Outcome::failure(message: 'Este exercício não está mais disponível. Encerre a sessão e comece outra.');
            }

            $answer = $answer === null ? null : trim($answer);
            $failure = $this->validate($exercise, $answer, $chosenPrincipleId);

            if ($failure !== null) {
                return Outcome::failure(message: $failure);
            }

            $attempt = $this->record->handle(
                $accessTokenId,
                $sessionId,
                $item['principle_id'],
                $type,
                $answer === '' ? null : $answer,
                max(0, $durationMs),
                $now ?? CarbonImmutable::now(),
                $exerciseId,
                $chosenPrincipleId,
            );

            $choiceIsCorrect = null;

            if ($exercise->choices !== []) {
                // A escolha é corrigida sem IA, comparando ids.
                $choiceIsCorrect = $chosenPrincipleId === $exercise->correctPrincipleId;
                $attempt->update([
                    'ai_verdict' => $choiceIsCorrect ? AiVerdict::Correct : AiVerdict::Incorrect,
                    'ai_suggested_rating' => $choiceIsCorrect ? SelfRating::Good : SelfRating::Again,
                ]);
            }

            $notice = null;

            if ($this->shouldEvaluateWithAi($type, $answer, $choiceIsCorrect)) {
                $notice = $this->evaluate->handle($attempt)->message;
            }

            return Outcome::success(data: new SubmittedAnswer($attempt, is_string($notice) ? $notice : null));
        } catch (\Exception $e) {
            Log::error("Erro ao gravar resposta de princípio: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar sua resposta. Tente de novo.');
        }
    }

    private function validate(PreparedExercise $exercise, ?string $answer, ?int $chosenPrincipleId): ?string
    {
        if ($exercise->choices !== [] && ! in_array($chosenPrincipleId, array_column($exercise->choices, 'principle_id'), true)) {
            return 'Escolha uma das opções antes de continuar.';
        }

        if ($exercise->choices === [] && $exercise->requiresAnswer && ($answer === null || $answer === '')) {
            return 'Escreva sua resposta antes de continuar.';
        }

        if (mb_strlen((string) $answer) > self::MAX_ANSWER_LENGTH) {
            return 'A resposta está longa demais. Resuma em até '.self::MAX_ANSWER_LENGTH.' caracteres.';
        }

        return null;
    }

    /**
     * Distinguir: a IA só avalia a justificativa de quem acertou a escolha, se a config permitir.
     */
    private function shouldEvaluateWithAi(ExerciseType $type, ?string $answer, ?bool $choiceIsCorrect): bool
    {
        if ($answer === null || $answer === '' || ! $this->strategies->for($type)->evaluatesWithAi()) {
            return false;
        }

        return $choiceIsCorrect === null || ($choiceIsCorrect && config('principios.ai.evaluate_distinction_rationale'));
    }
}
