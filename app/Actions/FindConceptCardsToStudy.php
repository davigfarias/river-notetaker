<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConceptDeckCard;
use Illuminate\Database\Eloquent\Collection;

final readonly class FindConceptCardsToStudy
{
    /**
     * Active cards of one deck, hardest first: most misses, then never
     * reviewed, then least practised. The token filter on the counters lives
     * inside the join's ON clause so that never-reviewed cards (no counter
     * row) are still returned.
     *
     * @return Collection<int, ConceptDeckCard>
     */
    public function handle(int $accessTokenId, int $deckId, int $limit = 50): Collection
    {
        return ConceptDeckCard::query()
            ->select('concept_deck_cards.*')
            ->where('concept_deck_cards.concept_deck_id', $deckId)
            ->active()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->leftJoin('concept_recall_counters as crc', fn ($join) => $join
                ->on('crc.concept_id', '=', 'concept_deck_cards.concept_id')
                ->where('crc.access_token_id', '=', $accessTokenId))
            ->orderByRaw('coalesce(crc.missed_count, 0) desc')
            ->orderByRaw('crc.last_reviewed_at is not null')
            ->orderByRaw('coalesce(crc.aced_count, 0) + coalesce(crc.missed_count, 0) asc')
            ->orderBy('concept_deck_cards.id')
            ->limit($limit)
            ->get();
    }
}
