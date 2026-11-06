<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\PromptVersions;
use App\Enums\ExerciseStatus;
use App\Enums\ExerciseType;
use App\Models\PrincipleExercise;

/**
 * Exercício pronto do cache: só Ready e com o mesmo conteúdo-base (source_hash) do princípio hoje.
 * Entre vários, o menos servido e o servido há mais tempo (nunca servido primeiro).
 */
final readonly class FindCachedExercise
{
    public function handle(int $accessTokenId, int $principleId, ExerciseType $type, string $sourceHash): ?PrincipleExercise
    {
        return PrincipleExercise::query()
            ->where('access_token_id', $accessTokenId)
            ->where('principle_id', $principleId)
            ->where('type', $type)
            ->where('status', ExerciseStatus::Ready)
            ->where('source_hash', $sourceHash)
            ->where('prompt_version', PromptVersions::for($type))
            ->orderBy('times_served')
            ->orderByRaw('last_served_at is null desc')
            ->orderBy('last_served_at')
            ->first();
    }
}
