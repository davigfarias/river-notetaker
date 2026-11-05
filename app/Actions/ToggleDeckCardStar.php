<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ConceptDeckCard;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class ToggleDeckCardStar
{
    public function handle(int $cardId, int $accessTokenId): Outcome
    {
        try {
            $card = ConceptDeckCard::query()
                ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->findOrFail($cardId);

            $card->update(['is_starred' => ! $card->is_starred]);

            return Outcome::success(
                message: $card->is_starred ? 'Cartão marcado com estrela.' : 'Estrela removida.',
                data: $card,
            );
        } catch (\Exception $e) {
            Log::error("Erro ao marcar estrela no cartão: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível marcar a estrela.');
        }
    }
}
