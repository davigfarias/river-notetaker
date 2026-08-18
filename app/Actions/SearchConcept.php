<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ConceptsDTO;
use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class SearchConcept
{
    public function handle(string $search): Outcome
    {
        try {
            $data = Concepts::search($search)
                ->get()
                ->map(fn (Concepts $concept): ConceptsDTO => ConceptsDTO::fromModel($concept));

            return Outcome::success(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: '');
        }
    }
}
