<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Planner;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetPlanner
{
    public function handle(int $id, int $accessTokenId): Outcome
    {
        try {
            $planner = Planner::query()
                ->where('access_token_id', $accessTokenId)
                ->withProgress()
                ->with(['items.referenceMaterial:id,title,author,type', 'items.chapter:id,title'])
                ->find($id);

            return Outcome::noViewMessage(data: $planner);
        } catch (\Exception $e) {
            Log::error("Erro ao carregar o planner: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar o planner.');
        }
    }
}
