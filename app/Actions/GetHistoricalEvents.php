<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HistoricalEvent;
use App\Support\Outcome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class GetHistoricalEvents
{
    /**
     * A numeric search also matches events whose start or end year is that number.
     */
    public function handle(int $accessTokenId, ?string $search = null): Outcome
    {
        try {
            $term = Str::lower(trim((string) $search));

            $events = HistoricalEvent::query()
                ->where('access_token_id', $accessTokenId)
                ->when($term !== '', fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query
                        ->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(description) LIKE ?', ["%{$term}%"])
                        ->when(ctype_digit($term), fn (Builder $query) => $query
                            ->orWhere('start_year', (int) $term)
                            ->orWhere('end_year', (int) $term))
                ))
                ->orderBy('sort_key')
                ->orderBy('id')
                ->get();

            return Outcome::noViewMessage(data: $events);
        } catch (\Exception $e) {
            Log::error("Erro ao carregar o histórico: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar o histórico.', data: new Collection);
        }
    }
}
