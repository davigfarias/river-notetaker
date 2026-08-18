<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ConceptsDTO;
use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetConceptsByLetter
{
    public function handle(string $letter): Outcome
    {
        try {
            $data = Concepts::search('')
                ->query(fn ($builder) => $builder->where('term', 'like', $letter.'%'))
                ->get()
                ->map(fn (Concepts $concept): ConceptsDTO => ConceptsDTO::fromModel($concept));

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar conceitos por letra: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar os conceitos desta letra.');
        }
    }
}
