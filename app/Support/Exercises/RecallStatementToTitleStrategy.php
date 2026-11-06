<?php

declare(strict_types=1);

namespace App\Support\Exercises;

use App\DTO\PreparedExercise;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;

final readonly class RecallStatementToTitleStrategy implements ExerciseStrategy
{
    public function type(): ExerciseType
    {
        return ExerciseType::RecallStatementToTitle;
    }

    public function prepare(StudyPrinciple $principle): PreparedExercise
    {
        return new PreparedExercise(
            type: $this->type(),
            principleId: $principle->id,
            question: $principle->statement,
            instruction: 'Qual é o título deste princípio? Tente lembrar e escreva (opcional).',
            reference: $principle->title,
            referenceLabel: 'Título do princípio',
            requiresAnswer: false,
        );
    }
}
