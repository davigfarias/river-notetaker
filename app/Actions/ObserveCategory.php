<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PastoralAdvices;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class ObserveCategory
{
    public function handle(string $partial, int $limit = 5): Outcome
    {
        try {
            $partial = trim($partial);

            if ($partial === '') {
                return Outcome::success(data: collect());
            }

            $data = PastoralAdvices::query()
                ->selectRaw('MIN(category) as category')
                ->whereRaw('LOWER(category) LIKE ?', [Str::lower($partial).'%'])
                ->groupByRaw('LOWER(category)')
                ->orderByRaw('LOWER(category)')
                ->limit($limit)
                ->pluck('category');

            return Outcome::success(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: '', data: collect());
        }
    }
}
