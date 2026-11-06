<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\FindOpenPrincipleSession;
use App\Actions\PrepareExercise;
use App\Actions\RecordPrincipleAttempt;
use App\Enums\ExerciseType;
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
    ) {}

    /**
     * Devolve o PrincipleAttempt gravado (ainda sem autoavaliação) em `data`.
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

            return Outcome::success(data: $this->record->handle(
                $accessTokenId,
                $sessionId,
                $item['principle_id'],
                $type,
                $answer === '' ? null : $answer,
                max(0, $durationMs),
                $now ?? CarbonImmutable::now(),
            ));
        } catch (\Exception $e) {
            Log::error("Erro ao gravar resposta de princípio: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar sua resposta. Tente de novo.');
        }
    }
}
