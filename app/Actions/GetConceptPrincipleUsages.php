<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PrincipleType;
use App\Models\Principle;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetConceptPrincipleUsages
{
    /**
     * @param  array<int, int>  $conceptIds
     */
    public function handle(array $conceptIds): Outcome
    {
        try {
            $data = Principle::query()
                ->where('type', PrincipleType::Concept)
                ->whereIn('concept_id', $conceptIds)
                ->with('principleTopic')
                ->get()
                ->groupBy('concept_id');

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar usos do conceito: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar os usos do conceito.', data: collect());
        }
    }
}
