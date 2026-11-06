<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PlannerItem;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

/**
 * Marca ou desmarca a leitura de um item. Vale só para este planner.
 */
final readonly class TogglePlannerItemRead
{
    public function handle(int $itemId, int $accessTokenId): Outcome
    {
        try {
            $item = PlannerItem::query()
                ->whereHas('planner', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->findOrFail($itemId);

            $item->update(['read_at' => $item->isRead() ? null : now()]);

            return Outcome::noViewMessage(data: $item);
        } catch (\Exception $e) {
            Log::error("Erro ao marcar leitura no planner: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível marcar a leitura.');
        }
    }
}
