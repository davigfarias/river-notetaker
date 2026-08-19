<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class ObserveTerm
{
    public function handle(string $term): Outcome
    {
        try {
            $existing = Concepts::whereRaw('LOWER(term) = ?', [Str::lower($term)])
                ->first();

            return Outcome::success(data: $existing);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: '');
        }
    }
}
