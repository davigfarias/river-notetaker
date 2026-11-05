<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConceptDeckCard;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class RemoveCardsFromDeck
{
    /**
     * @param  array<int, mixed>  $cardIds
     */
    public function handle(int $deckId, array $cardIds, int $accessTokenId): Outcome
    {
        try {
            $removed = ConceptDeckCard::query()
                ->where('concept_deck_id', $deckId)
                ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->whereIn('id', array_map('intval', $cardIds))
                ->delete();

            return Outcome::success(
                message: $removed === 1 ? '1 conceito tirado do deck.' : "{$removed} conceitos tirados do deck.",
                data: $removed,
            );
        } catch (\Exception $e) {
            Log::error("Erro ao tirar conceitos do deck: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível tirar os conceitos do deck.');
        }
    }
}
