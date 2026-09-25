<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Principle;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class DeletePrinciple
{
    public function handle(int $principleId): Outcome
    {
        try {
            Principle::destroy($principleId);

            return Outcome::success(message: 'Removido do tema.');
        } catch (\Exception $e) {
            Log::error("Erro ao remover princípio: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível remover.');
        }
    }
}
