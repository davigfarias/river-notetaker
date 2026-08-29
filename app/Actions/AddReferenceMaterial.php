<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ReferenceMaterialForm;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class AddReferenceMaterial
{
    public function handle(ReferenceMaterialForm $form, int $accessTokenId): Outcome
    {
        try {
            $material = ReferenceMaterial::create([
                ...$form->toAttributes(),
                'access_token_id' => $accessTokenId,
            ]);

            return Outcome::success(message: "Obra '{$material->title}' adicionada à biblioteca.", data: $material);
        } catch (\Exception $e) {
            Log::error("Erro ao adicionar material de referência: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível adicionar a obra.');
        }
    }
}
