<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConceptActivityRange;
use App\Models\ConceptReview;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class ComputeConceptActivityChart
{
    /**
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    public function handle(int $accessTokenId, ConceptActivityRange $range, CarbonImmutable $now): array
    {
        return match ($range) {
            ConceptActivityRange::Week => $this->buildDaily($accessTokenId, $now),
            ConceptActivityRange::Month => $this->buildWeekly($accessTokenId, $now),
            ConceptActivityRange::All => $this->buildMonthly($accessTokenId, $now),
        };
    }

    /**
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    private function buildDaily(int $accessTokenId, CarbonImmutable $now): array
    {
        $windowStart = $now->subDays(6)->startOfDay();
        $counts = $this->countsByKey($accessTokenId, $windowStart, fn (CarbonImmutable $date): string => $date->format('Y-m-d'));

        $buckets = collect(range(6, 0))->map(fn (int $daysAgo): array => [
            'label' => $now->subDays($daysAgo)->translatedFormat('D'),
            'key' => $now->subDays($daysAgo)->format('Y-m-d'),
        ]);

        return $this->formatBuckets($buckets, $counts);
    }

    /**
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    private function buildWeekly(int $accessTokenId, CarbonImmutable $now): array
    {
        $windowStart = $now->subDays(27)->startOfDay();
        $counts = $this->countsByKey(
            $accessTokenId,
            $windowStart,
            fn (CarbonImmutable $date): string => (string) min(3, intdiv((int) $windowStart->diffInDays($date), 7)),
        );

        $buckets = collect(range(0, 3))->map(fn (int $week): array => [
            'label' => 'S'.($week + 1),
            'key' => (string) $week,
        ]);

        return $this->formatBuckets($buckets, $counts);
    }

    /**
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    private function buildMonthly(int $accessTokenId, CarbonImmutable $now): array
    {
        $windowStart = $now->subMonths(5)->startOfMonth();
        $counts = $this->countsByKey($accessTokenId, $windowStart, fn (CarbonImmutable $date): string => $date->format('Y-m'));

        $buckets = collect(range(5, 0))->map(fn (int $monthsAgo): array => [
            'label' => $now->subMonths($monthsAgo)->translatedFormat('M'),
            'key' => $now->subMonths($monthsAgo)->format('Y-m'),
        ]);

        return $this->formatBuckets($buckets, $counts);
    }

    /**
     * @return Collection<string, int>
     */
    private function countsByKey(int $accessTokenId, CarbonImmutable $since, \Closure $keyFor): Collection
    {
        return ConceptReview::query()
            ->where('access_token_id', $accessTokenId)
            ->where('reviewed_at', '>=', $since)
            ->pluck('reviewed_at')
            ->map($keyFor)
            ->countBy();
    }

    /**
     * @param  Collection<int, array{label: string, key: string}>  $buckets
     * @param  Collection<string, int>  $counts
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    private function formatBuckets(Collection $buckets, Collection $counts): array
    {
        $max = max($counts->max() ?? 0, 1);

        return $buckets
            ->map(function (array $bucket) use ($counts, $max): array {
                $count = $counts->get($bucket['key'], 0);

                return [
                    'label' => $bucket['label'],
                    'count' => $count,
                    'percent' => (int) round(($count / $max) * 100),
                ];
            })
            ->all();
    }
}
