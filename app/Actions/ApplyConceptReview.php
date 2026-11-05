<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ConceptRecallCounters;
use App\Models\ConceptRecallCounter;
use Carbon\CarbonImmutable;

final readonly class ApplyConceptReview
{
    /**
     * Writes the new counters and the review time onto the token's counter row
     * for this concept (shared by every deck that holds the concept).
     */
    public function handle(int $accessTokenId, int $conceptId, ConceptRecallCounters $counters, ?CarbonImmutable $reviewedAt): ConceptRecallCounter
    {
        return ConceptRecallCounter::updateOrCreate(
            ['access_token_id' => $accessTokenId, 'concept_id' => $conceptId],
            [
                'aced_count' => $counters->acedCount,
                'missed_count' => $counters->missedCount,
                'last_reviewed_at' => $reviewedAt,
            ],
        );
    }
}
