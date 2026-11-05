<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConceptReviewResult;
use App\Models\ConceptReview;
use Carbon\CarbonImmutable;

final readonly class ComputeConceptTodayRecall
{
    /**
     * `rememberedPercent` is null when nothing was studied today (the UI shows "—").
     *
     * @return array{reviewedToday: int, rememberedPercent: ?int}
     */
    public function handle(int $accessTokenId, CarbonImmutable $now): array
    {
        $todays = ConceptReview::query()
            ->where('access_token_id', $accessTokenId)
            ->whereBetween('reviewed_at', [$now->startOfDay(), $now->endOfDay()]);

        $reviewedToday = (clone $todays)->count();

        if ($reviewedToday === 0) {
            return ['reviewedToday' => 0, 'rememberedPercent' => null];
        }

        $remembered = (clone $todays)->where('result', ConceptReviewResult::Remembered)->count();

        return [
            'reviewedToday' => $reviewedToday,
            'rememberedPercent' => (int) round(($remembered / $reviewedToday) * 100),
        ];
    }
}
