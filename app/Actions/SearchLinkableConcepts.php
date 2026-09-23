<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ConceptsDTO;
use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class SearchLinkableConcepts
{
    /**
     * @param  array<int, int>  $excludeIds
     */
    public function handle(string $term, array $excludeIds, int $limit = 8): Outcome
    {
        try {
            $data = Concepts::search($term)
                ->get()
                ->reject(fn (Concepts $concept): bool => in_array($concept->id, $excludeIds, true))
                ->take($limit)
                ->map(fn (Concepts $concept): ConceptsDTO => ConceptsDTO::fromModel($concept))
                ->values();

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro ao pesquisar conceitos relacionáveis: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível pesquisar conceitos.', data: collect());
        }
    }
}
