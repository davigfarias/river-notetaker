<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Planner;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetPlanners
{
    public function handle(int $accessTokenId): Outcome
    {
        try {
            $planners = Planner::query()
                ->where('access_token_id', $accessTokenId)
                ->withProgress()
                ->latest('id')
                ->get();

            return Outcome::noViewMessage(data: $planners);
        } catch (\Exception $e) {
            Log::error("Erro ao listar planners: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível carregar os planners.');
        }
    }
}
