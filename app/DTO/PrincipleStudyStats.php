<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Desempenho dos últimos dias. `levels`: rótulo do nível de Bloom => respostas e taxa de "Bom/Fácil"
 * (nulo se não houve respostas). `agreement`: comparação entre a nota sugerida pela IA e a nota do usuário
 * (nulo se nunca houve as duas).
 */
final readonly class PrincipleStudyStats
{
    /**
     * @param  array<string, array{answered: int, goodRate: float|null}>  $levels
     * @param  array{compared: int, same: int, aiHigher: int, aiLower: int}|null  $agreement
     */
    public function __construct(
        public int $answered,
        public array $levels,
        public ?array $agreement,
    ) {}
}
