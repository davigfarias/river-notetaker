<?php

declare(strict_types=1);

namespace App\Support\Exercises;

use App\Enums\ExerciseType;

/**
 * Mapa tipo => estratégia. Tipos sem estratégia registrada não são sorteados.
 */
final readonly class ExerciseStrategies
{
    /** @var array<string, ExerciseStrategy> */
    private array $byType;

    /**
     * @param  list<ExerciseStrategy>  $strategies
     */
    public function __construct(array $strategies)
    {
        $this->byType = array_combine(
            array_map(fn (ExerciseStrategy $s): string => $s->type()->value, $strategies),
            $strategies,
        );
    }

    public function for(ExerciseType $type): ExerciseStrategy
    {
        return $this->byType[$type->value] ?? throw new \DomainException("Sem estratégia para {$type->value}.");
    }

    /**
     * @return list<ExerciseType>
     */
    public function availableTypes(): array
    {
        return array_map(fn (ExerciseStrategy $s): ExerciseType => $s->type(), array_values($this->byType));
    }
}
