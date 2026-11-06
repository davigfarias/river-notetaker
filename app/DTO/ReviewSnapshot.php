<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\CarbonImmutable;

/**
 * Estado de agendamento de um princípio, sem banco: entrada e saída do Sm2Scheduler.
 */
final readonly class ReviewSnapshot
{
    public function __construct(
        public int $intervalDays,
        public float $ease,
        public int $repetitions,
        public int $lapses,
        public ?CarbonImmutable $dueAt = null,
        public ?CarbonImmutable $lastReviewedAt = null,
    ) {}
}
