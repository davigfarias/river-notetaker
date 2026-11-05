<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConceptDeck;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class DeleteConceptDeck
{
    /**
     * Removes the deck and its card list only: concepts, recall counters
     * and review history stay untouched.
     */
    public function handle(int $deckId, int $accessTokenId): Outcome
    {
        try {
            ConceptDeck::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($deckId)
                ->delete();

            return Outcome::success(message: 'Deck apagado. Os conceitos continuam no Dicionário.');
        } catch (\Exception $e) {
            Log::error("Erro ao apagar deck de conceitos: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível apagar o deck.');
        }
    }
}
