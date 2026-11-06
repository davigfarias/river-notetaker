<?php

declare(strict_types=1);

namespace App\Support\Exercises;

use App\DTO\PreparedExercise;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;

final readonly class RecallTitleToStatementStrategy implements ExerciseStrategy
{
    public function type(): ExerciseType
    {
        return ExerciseType::RecallTitleToStatement;
    }

    public function evaluatesWithAi(): bool
    {
        return false;
    }

    public function prepare(StudyPrinciple $principle): PreparedExercise
    {
        return new PreparedExercise(
            type: $this->type(),
            principleId: $principle->id,
            question: $principle->title,
            instruction: 'Tente lembrar o enunciado deste princípio e escreva com suas palavras (opcional).',
            reference: $principle->statement,
            referenceLabel: 'Enunciado do princípio',
            requiresAnswer: false,
        );
    }
}
