<?php

declare(strict_types=1);

namespace App\Support\Exercises;

use App\DTO\PreparedExercise;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;

/** Interrogação elaborativa: a pergunta é fixa e a IA avalia a explicação do aluno. */
final readonly class ExplainWhyStrategy implements ExerciseStrategy
{
    public function type(): ExerciseType
    {
        return ExerciseType::ExplainWhy;
    }

    public function evaluatesWithAi(): bool
    {
        return true;
    }

    public function prepare(StudyPrinciple $principle): PreparedExercise
    {
        return new PreparedExercise(
            type: $this->type(),
            principleId: $principle->id,
            question: $principle->title."\n\n".$principle->statement,
            instruction: 'Por que isso é verdade? Explique com suas palavras.',
            reference: $principle->statement,
            referenceLabel: 'Enunciado do princípio',
            requiresAnswer: true,
        );
    }
}
