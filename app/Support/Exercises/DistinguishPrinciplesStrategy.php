<?php

declare(strict_types=1);

namespace App\Support\Exercises;

use App\DTO\DistinctionPayload;
use App\DTO\PreparedExercise;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;
use App\Exceptions\InvalidExercisePayload;

/** A escolha é corrigida sem IA (compara ids); a IA só avalia a justificativa, se ligada. */
final readonly class DistinguishPrinciplesStrategy implements ExerciseStrategy
{
    public function type(): ExerciseType
    {
        return ExerciseType::DistinguishPrinciples;
    }

    public function evaluatesWithAi(): bool
    {
        return true;
    }

    public function prepare(StudyPrinciple $principle, ?array $payload = null): PreparedExercise
    {
        $distinction = DistinctionPayload::fromArray($payload ?? throw new InvalidExercisePayload('Caso não gerado.'));
        $correct = collect($distinction->candidates)->firstWhere('principle_id', $distinction->correctPrincipleId);

        return new PreparedExercise(
            type: $this->type(),
            principleId: $principle->id,
            question: $distinction->scenario,
            instruction: 'Qual destes princípios é o mais pertinente a este caso? Escolha um e, se quiser, justifique em uma frase.',
            reference: 'Princípio mais pertinente: '.$correct['title']."\n\n".$distinction->rationale,
            referenceLabel: 'Resposta e justificativa',
            requiresAnswer: true,
            choices: $distinction->candidates,
            correctPrincipleId: $distinction->correctPrincipleId,
            referenceKey: $distinction->rationale,
        );
    }
}
