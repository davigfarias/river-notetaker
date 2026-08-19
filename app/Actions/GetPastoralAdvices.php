<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PastoralAdvices;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetPastoralAdvices
{
    public function handle(): Outcome
    {
        try {

            $data = PastoralAdvices::all()
                ->map();

            return Outcome::noViewMessage();
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: '');
        }
    }
}
