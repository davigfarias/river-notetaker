<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HistoricalEvent;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class DeleteHistoricalEvent
{
    public function handle(int $historicalEventId, int $accessTokenId): Outcome
    {
        try {
            $event = HistoricalEvent::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($historicalEventId);

            $event->delete();

            return Outcome::success(message: 'Evento removido do histórico.');
        } catch (\Exception $e) {
            Log::error("Erro ao remover evento histórico: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível remover o evento.');
        }
    }
}
