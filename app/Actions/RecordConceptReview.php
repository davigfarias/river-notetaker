<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConceptReviewResult;
use App\Models\ConceptReview;
use Carbon\CarbonImmutable;

final readonly class RecordConceptReview
{
    public function handle(int $accessTokenId, int $conceptId, ConceptReviewResult $result, CarbonImmutable $now): ConceptReview
    {
        return ConceptReview::create([
            'access_token_id' => $accessTokenId,
            'concept_id' => $conceptId,
            'result' => $result,
            'reviewed_at' => $now,
        ]);
    }
}
