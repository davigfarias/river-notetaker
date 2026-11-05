<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConceptDeckCard;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class SetDeckCardsActiveState
{
    /**
     * @param  array<int, mixed>  $cardIds
     */
    public function handle(int $deckId, array $cardIds, bool $isActive, int $accessTokenId): Outcome
    {
        try {
            $changed = ConceptDeckCard::query()
                ->where('concept_deck_id', $deckId)
                ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->whereIn('id', array_map('intval', $cardIds))
                ->update(['is_active' => $isActive]);

            $verb = $isActive ? 'reativado' : 'desativado';

            return Outcome::success(
                message: $changed === 1 ? "1 cartão {$verb}." : "{$changed} cartões {$verb}s.",
                data: $changed,
            );
        } catch (\Exception $e) {
            Log::error("Erro ao alterar cartões do deck: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível alterar os cartões.');
        }
    }
}
