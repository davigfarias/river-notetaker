<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\HistoricalEventForm;
use App\Models\HistoricalEvent;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class AddHistoricalEvent
{
    public function handle(HistoricalEventForm $form, int $accessTokenId): Outcome
    {
        try {
            $event = HistoricalEvent::create([
                ...$form->toAttributes(),
                'access_token_id' => $accessTokenId,
            ]);

            return Outcome::success(message: "'{$event->title}' entrou no histórico.", data: $event);
        } catch (\Exception $e) {
            Log::error("Erro ao registrar evento histórico: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível registrar o evento.');
        }
    }
}
