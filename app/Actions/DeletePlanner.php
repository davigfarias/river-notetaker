<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Planner;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

/**
 * Apaga só o planner e seus itens. Obras e capítulos não são tocados.
 */
final readonly class DeletePlanner
{
    public function handle(int $id, int $accessTokenId): Outcome
    {
        try {
            Planner::query()->where('access_token_id', $accessTokenId)->findOrFail($id)->delete();

            return Outcome::success(message: 'Planner excluído. As obras continuam na sua biblioteca.');
        } catch (\Exception $e) {
            Log::error("Erro ao excluir o planner: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível excluir o planner.');
        }
    }
}
