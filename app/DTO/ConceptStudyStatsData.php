<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\CarbonImmutable;

final readonly class ConceptStudyStatsData
{
    /**
     * @param  array<int, array{label: string, count: int, percent: int}>  $activity
     * @param  array<int, array{id: int, name: string, meta: string, urgent: bool, dim: bool, cardsCount: int, activeCount: int, lastReviewedAt: ?CarbonImmutable}>  $decks
     */
    public function __construct(
        public int $toReinforce,
        public int $reviewedToday,
        public ?int $rememberedTodayPercent,
        public ConceptStreakData $streak,
        public array $activity,
        public array $decks,
    ) {}
}
