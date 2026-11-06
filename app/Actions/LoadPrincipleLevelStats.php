<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleAttempt;

/**
 * Quantas vezes (e quando foi a última) cada princípio foi praticado em cada nível de Bloom.
 */
final readonly class LoadPrincipleLevelStats
{
    /**
     * @param  array<int, int>  $principleIds
     * @return array<int, array<string, array{count: int, last: int|null}>>
     */
    public function handle(int $accessTokenId, array $principleIds): array
    {
        return PrincipleAttempt::query()
            ->where('access_token_id', $accessTokenId)
            ->whereIn('principle_id', $principleIds)
            ->whereNotNull('self_rating')
            ->selectRaw('principle_id, bloom_level, count(*) as total, max(answered_at) as last_at')
            ->groupBy('principle_id', 'bloom_level')
            ->get()
            ->groupBy('principle_id')
            ->map(fn ($rows): array => $rows->mapWithKeys(fn ($row): array => [
                (string) $row->getRawOriginal('bloom_level') => [
                    'count' => (int) $row->getAttribute('total'),
                    'last' => $row->getAttribute('last_at') !== null ? (int) strtotime((string) $row->getAttribute('last_at')) : null,
                ],
            ])->all())
            ->all();
    }
}
