<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\ComputeConceptActivityChart;
use App\Actions\ComputeConceptStudyStreak;
use App\Actions\ComputeConceptTodayRecall;
use App\Actions\CountConceptsToReinforce;
use App\Actions\GetConceptDecksSummary;
use App\DTO\ConceptStudyStatsData;
use App\Enums\ConceptActivityRange;
use Carbon\CarbonImmutable;

final readonly class BuildConceptStudyStatsOrchestrator
{
    public function __construct(
        private CountConceptsToReinforce $countToReinforce,
        private ComputeConceptTodayRecall $computeTodayRecall,
        private ComputeConceptStudyStreak $computeStreak,
        private ComputeConceptActivityChart $computeActivity,
        private GetConceptDecksSummary $getDecksSummary,
    ) {}

    public function handle(int $accessTokenId, ConceptActivityRange $range): ConceptStudyStatsData
    {
        $now = CarbonImmutable::now();
        $today = $this->computeTodayRecall->handle($accessTokenId, $now);

        return new ConceptStudyStatsData(
            toReinforce: $this->countToReinforce->handle($accessTokenId),
            reviewedToday: $today['reviewedToday'],
            rememberedTodayPercent: $today['rememberedPercent'],
            streak: $this->computeStreak->handle($accessTokenId, $now),
            activity: $this->computeActivity->handle($accessTokenId, $range, $now),
            decks: $this->getDecksSummary->handle($accessTokenId, $now)->all(),
        );
    }
}
