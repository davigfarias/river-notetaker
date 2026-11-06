<?php

declare(strict_types=1);

namespace App\Support\Exercises;

use App\DTO\PreparedExercise;
use App\DTO\ScenarioPayload;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;
use App\Exceptions\InvalidExercisePayload;

final readonly class ApplyScenarioStrategy implements ExerciseStrategy
{
    public function type(): ExerciseType
    {
        return ExerciseType::ApplyScenario;
    }

    public function evaluatesWithAi(): bool
    {
        return true;
    }

    public function prepare(StudyPrinciple $principle, ?array $payload = null): PreparedExercise
    {
        $scenario = ScenarioPayload::fromArray($payload ?? throw new InvalidExercisePayload('Cenário não gerado.'));
        $expected = $scenario->expectedApplication.($scenario->keyPoints === [] ? '' : "\n\nPontos-chave:\n- ".implode("\n- ", $scenario->keyPoints));

        return new PreparedExercise(
            type: $this->type(),
            principleId: $principle->id,
            question: $scenario->scenario,
            instruction: $scenario->question,
            reference: $expected,
            referenceLabel: 'Aplicação esperada',
            requiresAnswer: true,
            referenceKey: $expected,
        );
    }
}
