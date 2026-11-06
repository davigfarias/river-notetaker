<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\CarbonImmutable;

final readonly class PrincipleSessionSummary
{
    /**
     * @param  array<string, int>  $byLevel  rótulo do nível de Bloom => itens feitos
     * @param  array<int, int>  $missedPrincipleIds
     */
    public function __construct(
        public int $answered,
        public array $byLevel,
        public int $totalSeconds,
        public array $missedPrincipleIds,
        public ?CarbonImmutable $nextDueAt,
    ) {}
}
