<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetReferenceMaterial
{
    public function handle(int $id, int $accessTokenId): Outcome
    {
        try {
            $material = ReferenceMaterial::query()
                ->where('access_token_id', $accessTokenId)
                ->with(['citations' => fn ($query) => $query->orderByDesc('id')])
                ->withCount('citations')
                ->find($id);

            return Outcome::noViewMessage(data: $material);
        } catch (\Exception $e) {
            Log::error("Erro ao carregar o material de referência: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar a obra selecionada.');
        }
    }
}
