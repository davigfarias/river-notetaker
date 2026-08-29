<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\CitationForm;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class AddCitation
{
    public function handle(int $referenceMaterialId, CitationForm $form, int $accessTokenId): Outcome
    {
        try {
            $material = ReferenceMaterial::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($referenceMaterialId);

            $citation = $material->citations()->create([
                ...$form->toAttributes(),
                'access_token_id' => $accessTokenId,
            ]);

            return Outcome::success(message: 'Citação registrada.', data: $citation);
        } catch (\Exception $e) {
            Log::error("Erro ao registrar citação: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível registrar a citação.');
        }
    }
}
