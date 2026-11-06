<?php

declare(strict_types=1);

namespace App\Support\Exercises;

use App\DTO\PreparedExercise;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;

/**
 * O usuário cria um exemplo; a IA só comenta (sem sugerir nota). Sem IA, ele se compara com o princípio.
 */
final readonly class CreateExampleStrategy implements ExerciseStrategy
{
    public function type(): ExerciseType
    {
        return ExerciseType::CreateExample;
    }

    public function evaluatesWithAi(): bool
    {
        return true;
    }

    public function prepare(StudyPrinciple $principle, ?array $payload = null): PreparedExercise
    {
        return new PreparedExercise(
            type: $this->type(),
            principleId: $principle->id,
            question: $principle->title."\n\n".$principle->statement,
            instruction: 'Crie um exemplo ou ilustração original (situação, texto bíblico ou analogia) em que este princípio se aplica.',
            reference: $principle->statement,
            referenceLabel: 'Compare seu exemplo com o princípio',
            requiresAnswer: true,
        );
    }
}
