<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConceptRecallCounter;

final readonly class CountConceptsToReinforce
{
    /**
     * Concepts the token missed more than it aced that still sit as an active
     * card in one of the token's decks. Counters are unique per token+concept,
     * so each concept counts once even when it is in several decks.
     */
    public function handle(int $accessTokenId): int
    {
        return ConceptRecallCounter::query()
            ->where('access_token_id', $accessTokenId)
            ->toReinforce()
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('concept_deck_cards')
                ->join('concept_decks', 'concept_decks.id', '=', 'concept_deck_cards.concept_deck_id')
                ->whereColumn('concept_deck_cards.concept_id', 'concept_recall_counters.concept_id')
                ->where('concept_deck_cards.is_active', true)
                ->where('concept_decks.access_token_id', $accessTokenId))
            ->count();
    }
}
