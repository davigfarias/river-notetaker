<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExerciseType;
use App\Models\PrincipleAttempt;
use Carbon\CarbonImmutable;

/**
 * Grava a resposta ainda sem autoavaliação. Tentativas anteriores da sessão que ficaram
 * sem nota (usuário recarregou a página) são descartadas para não contar em dobro.
 */
final readonly class RecordPrincipleAttempt
{
    public function handle(
        int $accessTokenId,
        int $sessionId,
        int $principleId,
        ExerciseType $type,
        ?string $answer,
        int $durationMs,
        CarbonImmutable $now,
        ?int $exerciseId = null,
        ?int $chosenPrincipleId = null,
    ): PrincipleAttempt {
        PrincipleAttempt::query()
            ->where('access_token_id', $accessTokenId)
            ->where('principle_session_id', $sessionId)
            ->whereNull('self_rating')
            ->delete();

        return PrincipleAttempt::query()->create([
            'access_token_id' => $accessTokenId,
            'principle_session_id' => $sessionId,
            'principle_id' => $principleId,
            'type' => $type,
            'bloom_level' => $type->bloomLevel(),
            'principle_exercise_id' => $exerciseId,
            'user_answer' => $answer,
            'chosen_principle_id' => $chosenPrincipleId,
            'duration_ms' => $durationMs,
            'answered_at' => $now,
        ]);
    }
}
