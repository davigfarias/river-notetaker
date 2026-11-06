<?php

declare(strict_types=1);

namespace App\Support\Exercises;

use App\DTO\FlawedInterpretationPayload;
use App\DTO\PreparedExercise;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;
use App\Exceptions\InvalidExercisePayload;

final readonly class EvaluateInterpretationStrategy implements ExerciseStrategy
{
    public function type(): ExerciseType
    {
        return ExerciseType::EvaluateInterpretation;
    }

    public function evaluatesWithAi(): bool
    {
        return true;
    }

    public function prepare(StudyPrinciple $principle, ?array $payload = null): PreparedExercise
    {
        $flawed = FlawedInterpretationPayload::fromArray($payload ?? throw new InvalidExercisePayload('Interpretação não gerada.'));

        return new PreparedExercise(
            type: $this->type(),
            principleId: $principle->id,
            question: $flawed->interpretation,
            instruction: 'Há algum problema aqui? Qual princípio é violado e por quê?',
            reference: $flawed->flawExplanation,
            referenceLabel: 'O problema era',
            requiresAnswer: true,
            referenceKey: $flawed->flawExplanation,
        );
    }
}
