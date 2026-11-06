<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\EvaluateAttemptAnswer;
use App\Actions\FindOpenPrincipleSession;
use App\Actions\LoadStudyPrinciple;
use App\Actions\PrepareExercise;
use App\Actions\RecordPrincipleAttempt;
use App\DTO\SubmittedAnswer;
use App\Enums\ExerciseType;
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
        private LoadStudyPrinciple $load,
        private EvaluateAttemptAnswer $evaluate,
    ) {}

    /**
     * Devolve um SubmittedAnswer em `data`: a tentativa gravada (ainda sem autoavaliação) e,
     * se a IA não pôde avaliar, o aviso para mostrar ao usuário.
     */
    public function handle(int $accessTokenId, int $sessionId, int $index, ?string $answer, int $durationMs, ?CarbonImmutable $now = null): Outcome
    {
        try {
            $session = $this->findSession->handle($accessTokenId);
            $item = $session?->composition[$index] ?? null;

            if ($session === null || $session->id !== $sessionId || $item === null || $session->answered_count !== $index) {
                return Outcome::failure(message: 'Esta sessão mudou. Recarregue a página para continuar de onde parou.');
            }

            $answer = $answer === null ? null : trim($answer);
            $type = ExerciseType::from($item['type']);

            if ($this->prepare->handle($item['principle_id'], $type)->requiresAnswer && ($answer === null || $answer === '')) {
                return Outcome::failure(message: 'Escreva sua resposta antes de continuar.');
            }

            if (mb_strlen((string) $answer) > self::MAX_ANSWER_LENGTH) {
                return Outcome::failure(message: 'A resposta está longa demais. Resuma em até '.self::MAX_ANSWER_LENGTH.' caracteres.');
            }

            $attempt = $this->record->handle(
                $accessTokenId,
                $sessionId,
                $item['principle_id'],
                $type,
                $answer === '' ? null : $answer,
                max(0, $durationMs),
                $now ?? CarbonImmutable::now(),
            );

            $notice = null;

            if ($answer !== null && $answer !== '' && $this->strategies->for($type)->evaluatesWithAi()) {
                $notice = $this->evaluate->handle($attempt, $this->load->handle($item['principle_id']))->message;
            }

            return Outcome::success(data: new SubmittedAnswer($attempt, is_string($notice) ? $notice : null));
        } catch (\Exception $e) {
            Log::error("Erro ao gravar resposta de princípio: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar sua resposta. Tente de novo.');
        }
    }
}
