<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\ExerciseType;

/**
 * O que a tela mostra para um exercício. `reference` só aparece depois de o usuário responder.
 */
final readonly class PreparedExercise
{
    public function __construct(
        public ExerciseType $type,
        public int $principleId,
        public string $question,
        public string $instruction,
        public string $reference,
        public string $referenceLabel,
        public bool $requiresAnswer,
    ) {}
}
