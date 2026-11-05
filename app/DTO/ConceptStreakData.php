<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ConceptStreakData
{
    /**
     * @param  array<int, bool>  $last7  oldest to newest, ending today
     */
    public function __construct(
        public int $days,
        public array $last7,
    ) {}
}
