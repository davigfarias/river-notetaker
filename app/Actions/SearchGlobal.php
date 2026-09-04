<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\SearchResultDTO;
use App\Models\Citation;
use App\Models\Concepts;
use App\Models\Notes;
use App\Models\PastoralAdvices;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class SearchGlobal
{
    public function handle(string $term, int $accessTokenId, int $limitPerType = 5): Outcome
    {
        try {
            $term = trim($term);

            if ($term === '') {
                return Outcome::noViewMessage(data: collect());
            }

            $notes = Notes::search($term)
                ->where('access_token_id', $accessTokenId)
                ->take($limitPerType)
                ->get()
                ->load('discipline')
                ->map(fn (Notes $note): SearchResultDTO => SearchResultDTO::fromNote($note));

            $advices = PastoralAdvices::search($term)
                ->take($limitPerType)
                ->get()
                ->map(fn (PastoralAdvices $advice): SearchResultDTO => SearchResultDTO::fromAdvice($advice, $term));

            $concepts = Concepts::search($term)
                ->take($limitPerType)
                ->get()
                ->map(fn (Concepts $concept): SearchResultDTO => SearchResultDTO::fromConcept($concept, $term));

            $references = ReferenceMaterial::search($term)
                ->where('access_token_id', $accessTokenId)
                ->take($limitPerType)
                ->get()
                ->map(fn (ReferenceMaterial $reference): SearchResultDTO => SearchResultDTO::fromReference($reference));

            $citations = Citation::search($term)
                ->where('access_token_id', $accessTokenId)
                ->take($limitPerType)
                ->get()
                ->load('referenceMaterial')
                ->map(fn (Citation $citation): SearchResultDTO => SearchResultDTO::fromCitation($citation));

            /** @var Collection<int, SearchResultDTO> $results */
            $results = $notes->concat($advices)->concat($concepts)->concat($references)->concat($citations);

            return Outcome::noViewMessage(data: $results);
        } catch (\Exception $e) {
            Log::error("Erro ao realizar busca global: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível realizar a busca.', data: collect());
        }
    }
}
