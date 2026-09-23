<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class LinkConcepts
{
    public function handle(int $conceptId, int $relatedConceptId): Outcome
    {
        if ($conceptId === $relatedConceptId) {
            return Outcome::failure(message: 'Um conceito não pode se relacionar consigo mesmo.');
        }

        try {
            DB::transaction(function () use ($conceptId, $relatedConceptId): void {
                Concepts::findOrFail($conceptId)->relatedConcepts()->syncWithoutDetaching([$relatedConceptId]);
                Concepts::findOrFail($relatedConceptId)->relatedConcepts()->syncWithoutDetaching([$conceptId]);
            });

            return Outcome::success(message: 'Conceitos relacionados com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao relacionar conceitos: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível relacionar os conceitos.');
        }
    }
}
