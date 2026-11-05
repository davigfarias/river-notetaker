<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConceptDeck;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class GetConceptDecksSummary
{
    /**
     * A deck counts as "urgent" once its reinforce pile reaches this size.
     */
    private const int URGENT_REINFORCE_COUNT = 20;

    /**
     * @return Collection<int, array{id: int, name: string, meta: string, urgent: bool, dim: bool, cardsCount: int, activeCount: int, lastReviewedAt: ?CarbonImmutable}>
     */
    public function handle(int $accessTokenId, CarbonImmutable $now, ?int $limit = null): Collection
    {
        // ponytail: three correlated subqueries per deck; fine for tens of decks, precompute counters if it ever gets slow.
        $lastReviewed = DB::table('concept_deck_cards as cdc')
            ->join('concept_recall_counters as lrc', fn ($join) => $join
                ->on('lrc.concept_id', '=', 'cdc.concept_id')
                ->where('lrc.access_token_id', '=', $accessTokenId))
            ->whereColumn('cdc.concept_deck_id', 'concept_decks.id')
            ->selectRaw('max(lrc.last_reviewed_at)');

        return ConceptDeck::query()
            ->where('access_token_id', $accessTokenId)
            ->withCount('cards')
            ->withCount(['cards as active_count' => fn ($query) => $query->active()])
            ->withCount(['cards as reinforce_count' => fn ($query) => $query
                ->active()
                ->whereExists(fn ($exists) => $exists
                    ->selectRaw('1')
                    ->from('concept_recall_counters as crc')
                    ->whereColumn('crc.concept_id', 'concept_deck_cards.concept_id')
                    ->where('crc.access_token_id', $accessTokenId)
                    ->whereColumn('crc.missed_count', '>', 'crc.aced_count')),
            ])
            ->addSelect(['cards_last_reviewed_at' => $lastReviewed])
            ->orderByRaw('cards_last_reviewed_at is null, cards_last_reviewed_at desc')
            ->orderByDesc('concept_decks.created_at')
            ->orderByDesc('concept_decks.id')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get()
            ->map(fn (ConceptDeck $deck): array => $this->summarize($deck, $now));
    }

    /**
     * @return array{id: int, name: string, meta: string, urgent: bool, dim: bool, cardsCount: int, activeCount: int, lastReviewedAt: ?CarbonImmutable}
     */
    private function summarize(ConceptDeck $deck, CarbonImmutable $now): array
    {
        $reinforceCount = (int) $deck->getAttribute('reinforce_count');
        $rawLast = $deck->getAttribute('cards_last_reviewed_at');
        $lastReviewedAt = $rawLast ? CarbonImmutable::parse($rawLast) : null;

        $meta = match (true) {
            $reinforceCount > 0 => "{$reinforceCount} para reforçar",
            $lastReviewedAt !== null && $lastReviewedAt >= $now->startOfDay() => 'Revisado hoje',
            default => 'Em dia',
        };

        return [
            'id' => $deck->id,
            'name' => $deck->name,
            'meta' => $meta,
            'urgent' => $reinforceCount >= self::URGENT_REINFORCE_COUNT,
            'dim' => $reinforceCount === 0,
            'cardsCount' => (int) $deck->getAttribute('cards_count'),
            'activeCount' => (int) $deck->getAttribute('active_count'),
            'lastReviewedAt' => $lastReviewedAt,
        ];
    }
}
