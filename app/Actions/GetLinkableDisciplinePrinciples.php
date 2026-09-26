<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Principle;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetLinkableDisciplinePrinciples
{
    public function handle(int $disciplineId): Outcome
    {
        try {
            $data = Principle::whereHas(
                'principleTopic.disciplines',
                fn ($query) => $query->where('disciplines.id', $disciplineId)
            )
                ->with('principleTopic', 'concept')
                ->get();

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar princípios linkáveis da disciplina: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar os princípios da disciplina.', data: collect());
        }
    }
}
