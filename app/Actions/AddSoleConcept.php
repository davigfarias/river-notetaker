<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\SoleConceptDTO;
use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class AddSoleConcept
{
    public function handle(SoleConceptDTO $concepts): Outcome
    {
        try {
            $query = Concepts::create([
                'term' => $concepts->term,
                'definition' => $concepts->definition,
            ]);

            return Outcome::success(data: $query, message: "Conceito '{$concepts->term}' criado com sucesso");
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar o conceito.');
        }
    }
}
