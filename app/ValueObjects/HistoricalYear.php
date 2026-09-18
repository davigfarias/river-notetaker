<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\Era;
use InvalidArgumentException;
use Stringable;

/**
 * A calendar year on the a.C./d.C. scale. There is no year zero: 1 a.C. is
 * immediately followed by 1 d.C., so the sort key skips zero entirely.
 */
final readonly class HistoricalYear implements Stringable
{
    public function __construct(
        public int $year,
        public Era $era,
    ) {
        if ($year < 1) {
            throw new InvalidArgumentException("O ano deve ser maior que zero: {$year}");
        }
    }

    /**
     * Signed integer that orders years chronologically (a.C. years are negative).
     */
    public function sortKey(): int
    {
        return $this->era === Era::BeforeChrist ? -$this->year : $this->year;
    }

    public function label(): string
    {
        return "{$this->year} {$this->era->label()}";
    }

    /**
     * "1337–1453 d.C." when both ends share the era, "27 a.C. – 14 d.C." otherwise.
     */
    public static function rangeLabel(self $start, ?self $end): string
    {
        if ($end === null || $end->sortKey() === $start->sortKey()) {
            return $start->label();
        }

        if ($start->era === $end->era) {
            return "{$start->year}–{$end->year} {$start->era->label()}";
        }

        return "{$start->label()} – {$end->label()}";
    }

    public function __toString(): string
    {
        return $this->label();
    }
}
