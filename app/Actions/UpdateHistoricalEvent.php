<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\HistoricalEventForm;
use App\Models\HistoricalEvent;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateHistoricalEvent
{
    public function handle(int $historicalEventId, HistoricalEventForm $form, int $accessTokenId): Outcome
    {
        try {
            $event = HistoricalEvent::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($historicalEventId);

            $event->update($form->toAttributes());

            return Outcome::success(message: 'Evento atualizado.', data: $event);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar evento histórico: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar o evento.');
        }
    }
}
