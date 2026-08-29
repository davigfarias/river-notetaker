<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ReferenceMaterialForm;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateReferenceMaterial
{
    public function handle(int $id, ReferenceMaterialForm $form, int $accessTokenId): Outcome
    {
        try {
            $material = ReferenceMaterial::query()
                ->where('access_token_id', $accessTokenId)
                ->findOrFail($id);

            $material->update($form->toAttributes());

            return Outcome::success(message: 'Obra atualizada com sucesso.', data: $material);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar material de referência: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar a obra.');
        }
    }
}
