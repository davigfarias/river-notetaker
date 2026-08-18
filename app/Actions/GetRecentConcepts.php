<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ConceptsDTO;
use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetRecentConcepts
{
    public function handle(int $limit = 3): Outcome
    {
        try {
            $data = Concepts::latest()
                ->limit($limit)
                ->get()
                ->map(fn (Concepts $concept): ConceptsDTO => ConceptsDTO::fromModel($concept));

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar conceitos recentes: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar os conceitos recentes.');
        }
    }
}
