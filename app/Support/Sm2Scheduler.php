<?php

declare(strict_types=1);

namespace App\Support;

use App\DTO\ReviewSnapshot;
use App\Enums\SelfRating;
use Carbon\CarbonImmutable;

/**
 * SM-2 simplificado, função pura: sem banco e sem relógio (recebe o `$now`).
 *
 * Errei: volta amanhã e conta uma falha. Difícil/Bom/Fácil crescem o intervalo.
 * Primeiras revisões fixas (1 dia, depois 3 ou 6); depois intervalo * facilidade.
 */
final readonly class Sm2Scheduler
{
    public function __construct(
        private float $initialEase = 2.5,
        private float $minEase = 1.3,
        private int $maxIntervalDays = 365,
    ) {}

    public function initial(): ReviewSnapshot
    {
        return new ReviewSnapshot(0, $this->initialEase, 0, 0);
    }

    public function next(ReviewSnapshot $current, SelfRating $rating, CarbonImmutable $now): ReviewSnapshot
    {
        [$interval, $ease, $repetitions, $lapses] = match ($rating) {
            SelfRating::Again => [1, $current->ease - 0.2, 0, $current->lapses + 1],
            SelfRating::Hard => [
                max(1, (int) round(max($current->intervalDays, 1) * 1.2)),
                $current->ease - 0.15,
                $current->repetitions + 1,
                $current->lapses,
            ],
            SelfRating::Good => [
                match ($current->repetitions) {
                    0 => 1,
                    1 => 3,
                    default => (int) round($current->intervalDays * $current->ease),
                },
                $current->ease,
                $current->repetitions + 1,
                $current->lapses,
            ],
            SelfRating::Easy => [
                match ($current->repetitions) {
                    0 => 3,
                    1 => 6,
                    default => (int) round($current->intervalDays * $current->ease * 1.3),
                },
                $current->ease + 0.15,
                $current->repetitions + 1,
                $current->lapses,
            ],
        };

        $interval = min(max($interval, 1), $this->maxIntervalDays);

        return new ReviewSnapshot(
            intervalDays: $interval,
            ease: round(max($ease, $this->minEase), 2),
            repetitions: $repetitions,
            lapses: $lapses,
            dueAt: $now->addDays($interval),
            lastReviewedAt: $now,
        );
    }
}
