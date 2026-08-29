<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class SearchReferenceMaterials
{
    public function handle(string $term, int $accessTokenId, int $limit = 20): Outcome
    {
        try {
            $term = trim($term);

            if ($term === '') {
                return Outcome::noViewMessage(data: collect());
            }

            $materials = ReferenceMaterial::search($term)
                ->where('access_token_id', $accessTokenId)
                ->take($limit)
                ->get()
                ->loadCount('citations');

            return Outcome::noViewMessage(data: $materials);
        } catch (\Exception $e) {
            Log::error("Erro ao pesquisar obras: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível pesquisar as obras.', data: collect());
        }
    }
}
