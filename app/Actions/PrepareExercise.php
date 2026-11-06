<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\PreparedExercise;
use App\Enums\ExerciseType;
use App\Models\PrincipleExercise;
use App\Support\Exercises\ExerciseStrategies;

/**
 * Monta o que a tela mostra. Nos tipos gerados pela IA, o conteúdo vem do exercício em cache
 * (`exerciseId`); sem ele, a estratégia lança InvalidExercisePayload.
 */
final readonly class PrepareExercise
{
    public function __construct(
        private ExerciseStrategies $strategies,
        private LoadStudyPrinciple $load,
    ) {}

    public function handle(int $principleId, ExerciseType $type, ?int $exerciseId = null): PreparedExercise
    {
        $payload = $type->requiresAiGeneration() && $exerciseId !== null
            ? PrincipleExercise::query()->where('principle_id', $principleId)->find($exerciseId)?->payload
            : null;

        return $this->strategies->for($type)->prepare($this->load->handle($principleId), $payload);
    }
}
