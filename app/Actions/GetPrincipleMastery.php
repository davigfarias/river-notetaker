<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BloomLevel;
use App\Enums\SelfRating;
use App\Models\PrincipleAttempt;
use App\Models\PrincipleReviewState;
use Carbon\CarbonImmutable;

/**
 * Domínio de cada princípio em cada nível de Bloom: fatia de "Bom/Fácil" nas últimas respostas
 * daquele nível (nulo = nunca praticado), e a data da próxima revisão.
 */
final readonly class GetPrincipleMastery
{
    private const RECENT_ATTEMPTS = 3;

    /**
     * @param  array<int, int>  $principleIds
     * @return array<int, array{levels: array<string, float|null>, dueAt: CarbonImmutable|null}>
     */
    public function handle(int $accessTokenId, array $principleIds): array
    {
        $attempts = PrincipleAttempt::query()
            ->where('access_token_id', $accessTokenId)
            ->whereIn('principle_id', $principleIds)
            ->whereNotNull('self_rating')
            ->orderByDesc('answered_at')
            ->get(['principle_id', 'bloom_level', 'self_rating'])
            ->groupBy('principle_id');

        $due = PrincipleReviewState::query()
            ->where('access_token_id', $accessTokenId)
            ->whereIn('principle_id', $principleIds)
            ->pluck('due_at', 'principle_id');

        return collect($principleIds)->mapWithKeys(fn (int $id): array => [$id => [
            'levels' => collect(BloomLevel::cases())->mapWithKeys(function (BloomLevel $level) use ($attempts, $id): array {
                $recent = ($attempts->get($id) ?? collect())->where('bloom_level', $level)->take(self::RECENT_ATTEMPTS);
                $good = $recent->filter(fn (PrincipleAttempt $a): bool => in_array($a->self_rating, [SelfRating::Good, SelfRating::Easy], true))->count();

                return [$level->value => $recent->isEmpty() ? null : (float) ($good / $recent->count())];
            })->all(),
            'dueAt' => $due->has($id) ? CarbonImmutable::parse($due[$id]) : null,
        ]])->all();
    }
}
