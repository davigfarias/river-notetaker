<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PlannerItem;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class RemovePlannerItem
{
    public function handle(int $itemId, int $accessTokenId): Outcome
    {
        try {
            PlannerItem::query()
                ->whereHas('planner', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->findOrFail($itemId)
                ->delete();

            return Outcome::success(message: 'Item retirado do planner.');
        } catch (\Exception $e) {
            Log::error("Erro ao retirar item do planner: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível retirar o item.');
        }
    }
}
