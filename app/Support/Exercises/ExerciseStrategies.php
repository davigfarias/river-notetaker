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
     * Tipos que podem ser sorteados. Sem IA, só os que funcionam offline.
     *
     * @return list<ExerciseType>
     */
    public function availableTypes(bool $aiEnabled): array
    {
        return array_values(array_filter(
            array_map(fn (ExerciseStrategy $s): ExerciseType => $s->type(), array_values($this->byType)),
            fn (ExerciseType $t): bool => $aiEnabled || $t->isOfflineCapable(),
        ));
    }
}
