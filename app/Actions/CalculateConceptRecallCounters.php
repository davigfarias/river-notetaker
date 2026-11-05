<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ConceptRecallCounters;
use App\Enums\ConceptReviewResult;

/**
 * Pure recall-counter math: "lembrei" adds to the aced count,
 * "não lembrei" adds to the missed count. No database, no clock.
 */
final readonly class CalculateConceptRecallCounters
{
    public function handle(int $acedCount, int $missedCount, ConceptReviewResult $result): ConceptRecallCounters
    {
        return new ConceptRecallCounters(
            acedCount: $result->remembered() ? $acedCount + 1 : $acedCount,
            missedCount: $result->remembered() ? $missedCount : $missedCount + 1,
        );
    }
}
