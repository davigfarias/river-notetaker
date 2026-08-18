<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ConceptsDTO;
use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetAllConcepts
{
    public function handle(): Outcome
    {
        try {
            $data = Concepts::all()
                ->map(fn (Concepts $concept): ConceptsDTO => ConceptsDTO::fromModel($concept));

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar seus conceitos');
        }
    }
}
