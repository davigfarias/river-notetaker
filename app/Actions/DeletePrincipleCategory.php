<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleCategory;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class DeletePrincipleCategory
{
    /**
     * Os princípios da categoria voltam para a lista sem categoria (FK nullOnDelete).
     */
    public function handle(int $categoryId): Outcome
    {
        try {
            PrincipleCategory::destroy($categoryId);

            return Outcome::success(message: 'Categoria removida. Os princípios foram mantidos.');
        } catch (\Exception $e) {
            Log::error("Erro ao remover categoria: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível remover a categoria.');
        }
    }
}
