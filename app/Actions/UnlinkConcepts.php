<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Concepts;
use App\Support\Outcome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class UnlinkConcepts
{
    public function handle(int $conceptId, int $relatedConceptId): Outcome
    {
        try {
            DB::transaction(function () use ($conceptId, $relatedConceptId): void {
                Concepts::findOrFail($conceptId)->relatedConcepts()->detach($relatedConceptId);
                Concepts::findOrFail($relatedConceptId)->relatedConcepts()->detach($conceptId);
            });

            return Outcome::success(message: 'Relação removida.');
        } catch (\Exception $e) {
            Log::error("Erro ao remover relação entre conceitos: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível remover a relação.');
        }
    }
}
