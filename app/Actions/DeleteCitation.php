<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Citation;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class DeleteCitation
{
    public function handle(int $id, int $accessTokenId): Outcome
    {
        try {
            $citation = Citation::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($id);

            $citation->delete();

            return Outcome::success(message: 'Citação removida.');
        } catch (\Exception $e) {
            Log::error("Erro ao remover citação: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível remover a citação.');
        }
    }
}
