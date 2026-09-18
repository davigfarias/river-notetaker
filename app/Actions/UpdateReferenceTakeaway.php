<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateReferenceTakeaway
{
    /**
     * Stores the single pinned line that sits above the reading notes of a work.
     */
    public function handle(int $referenceMaterialId, ?string $takeaway, int $accessTokenId): Outcome
    {
        try {
            $material = ReferenceMaterial::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($referenceMaterialId);

            $material->update(['notes_takeaway' => filled($takeaway) ? trim($takeaway) : null]);

            return Outcome::success(message: 'Resumo atualizado.', data: $material);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar o resumo das anotações: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar o resumo.');
        }
    }
}
