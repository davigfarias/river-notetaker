<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\PlannerForm;
use App\Models\Planner;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

/**
 * Cria o planner (com $plannerId nulo) ou atualiza nome, objetivo e data-alvo.
 */
final readonly class SavePlanner
{
    public function handle(PlannerForm $form, int $accessTokenId, ?int $plannerId = null): Outcome
    {
        try {
            if ($plannerId === null) {
                $planner = Planner::create([...$form->toAttributes(), 'access_token_id' => $accessTokenId]);

                return Outcome::success(message: "Planner '{$planner->name}' criado.", data: $planner);
            }

            $planner = Planner::query()->where('access_token_id', $accessTokenId)->findOrFail($plannerId);
            $planner->update($form->toAttributes());

            return Outcome::success(message: 'Planner atualizado.', data: $planner);
        } catch (\Exception $e) {
            Log::error("Erro ao salvar o planner: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível salvar o planner.');
        }
    }
}
