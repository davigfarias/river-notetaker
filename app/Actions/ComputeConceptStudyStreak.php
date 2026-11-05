<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ConceptStreakData;
use App\Models\ConceptReview;
use Carbon\CarbonImmutable;

final readonly class ComputeConceptStudyStreak
{
    public function handle(int $accessTokenId, CarbonImmutable $now): ConceptStreakData
    {
        // Bucketed in PHP: DATE()/DATE_FORMAT() are not portable between SQLite and MySQL.
        $reviewedDates = ConceptReview::query()
            ->where('access_token_id', $accessTokenId)
            ->where('reviewed_at', '>=', $now->subDays(60))
            ->pluck('reviewed_at')
            ->map(fn (CarbonImmutable $date): string => $date->format('Y-m-d'))
            ->unique();

        $cursor = $reviewedDates->contains($now->format('Y-m-d')) ? $now : $now->subDay();

        $days = 0;
        while ($reviewedDates->contains($cursor->format('Y-m-d'))) {
            $days++;
            $cursor = $cursor->subDay();
        }

        $last7 = collect(range(6, 0))
            ->map(fn (int $daysAgo): bool => $reviewedDates->contains($now->subDays($daysAgo)->format('Y-m-d')))
            ->all();

        return new ConceptStreakData(days: $days, last7: $last7);
    }
}
