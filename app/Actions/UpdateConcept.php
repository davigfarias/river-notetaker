<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\SoleConceptDTO;
use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class UpdateConcept
{
    public function handle(int $id, SoleConceptDTO $concept): Outcome
    {
        try {
            $model = Concepts::findOrFail($id);

            $model->update([
                'term' => $concept->term,
                'definition' => $concept->definition,
            ]);

            return Outcome::success(message: "Conceito '{$concept->term}' atualizado com sucesso.");
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar o conceito: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível atualizar o conceito.');
        }
    }
}
