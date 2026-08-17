<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetPastoralAdvices
{
    public function handle(): Outcome
    {
        try {
            return Outcome::noViewMessage();
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: '');
        }
    }
}
