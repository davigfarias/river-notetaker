<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\SoleConceptDTO;
use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class AddConceptToNote
{
    public function handle(int $noteId, SoleConceptDTO $concept): Outcome
    {
        try {
            $model = Concepts::create([
                'note_id' => $noteId,
                'term' => $concept->term,
                'definition' => $concept->definition,
            ]);

            return Outcome::success(data: $model, message: "Conceito '{$concept->term}' adicionado com sucesso.");
        } catch (\Exception $e) {
            Log::error("Erro ao adicionar o conceito: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível adicionar o conceito.');
        }
    }
}
