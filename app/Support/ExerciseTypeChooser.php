<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ExerciseType;
use Random\Randomizer;

/**
 * Escolhe o tipo de exercício de um princípio.
 *
 * Ordem: princípio novo começa por Recordar/Explicar/Aplicar; senão, quando a sessão
 * precisa de nível alto, só tipos de nível alto; senão, com muitas falhas, reconstrói a
 * base (Explicar/Recordar). Dentro do que sobrou, vence o nível menos praticado e,
 * no empate, o mais antigo.
 */
final readonly class ExerciseTypeChooser
{
    /** @var list<ExerciseType> */
    private const NEW_STARTERS = [ExerciseType::RecallTitleToStatement, ExerciseType::ExplainWhy, ExerciseType::ApplyScenario];

    /** @var list<ExerciseType> */
    private const REBUILD = [ExerciseType::ExplainWhy, ExerciseType::RecallTitleToStatement];

    private const LAPSES_TO_REBUILD = 2;

    public function __construct(private Randomizer $randomizer) {}

    /**
     * @param  list<ExerciseType>  $available
     * @param  array<string, array{count: int, last: int|null}>  $levelStats  indexado pelo valor do BloomLevel
     */
    public function choose(array $available, array $levelStats, bool $isNew, int $lapses, bool $needHigherOrder): ExerciseType
    {
        $keepIfAny = fn (array $pool, callable $filter): array => ($kept = array_values(array_filter($pool, $filter))) !== [] ? $kept : $pool;

        $pool = match (true) {
            $isNew => $keepIfAny($available, fn (ExerciseType $t): bool => in_array($t, self::NEW_STARTERS, true)),
            $needHigherOrder => $keepIfAny($available, fn (ExerciseType $t): bool => $t->bloomLevel()->isHigherOrder()),
            $lapses >= self::LAPSES_TO_REBUILD => $keepIfAny($available, fn (ExerciseType $t): bool => in_array($t, self::REBUILD, true)),
            default => $available,
        };

        if ($pool === []) {
            return ExerciseType::RecallTitleToStatement;
        }

        $ranked = $this->randomizer->shuffleArray($pool);
        usort($ranked, fn (ExerciseType $a, ExerciseType $b): int => $this->rank($a, $levelStats) <=> $this->rank($b, $levelStats));

        return $ranked[0];
    }

    /**
     * @param  array<string, array{count: int, last: int|null}>  $levelStats
     * @return array{int, int}
     */
    private function rank(ExerciseType $type, array $levelStats): array
    {
        $stat = $levelStats[$type->bloomLevel()->value] ?? ['count' => 0, 'last' => null];

        return [$stat['count'], $stat['last'] ?? 0];
    }
}
