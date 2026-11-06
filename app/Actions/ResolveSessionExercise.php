<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\PromptVersions;
use App\Enums\ExerciseType;
use App\Models\PrincipleExercise;
use App\Models\PrincipleSession;
use App\Support\Outcome;
use Carbon\CarbonImmutable;

/**
 * Deixa pronto o exercício do item atual da sessão. Tipos sem IA não precisam de nada.
 * Tipos gerados: cache primeiro; sem estoque, gera na hora; se não der, troca por um tipo
 * sem IA (e marca o item como `substituted` para a tela avisar). Grava `exercise_id` no item.
 */
final readonly class ResolveSessionExercise
{
    public function __construct(
        private LoadStudyPrinciple $load,
        private FindCachedExercise $cache,
        private GenerateExerciseForPrinciple $generate,
        private DispatchExerciseStock $dispatch,
    ) {}

    public function handle(int $accessTokenId, PrincipleSession $session, int $index, ?CarbonImmutable $now = null): Outcome
    {
        $now ??= CarbonImmutable::now();
        $composition = $session->composition;
        $item = $composition[$index] ?? null;

        if ($item === null) {
            return Outcome::failure('Item da sessão não encontrado.');
        }

        $type = ExerciseType::from($item['type']);

        if (! $type->requiresAiGeneration() || isset($item['exercise_id'])) {
            return Outcome::noViewMessage();
        }

        $principle = $this->load->handle($item['principle_id']);
        $hash = $principle->sourceHash();

        $exercise = $this->cache->handle($accessTokenId, $principle->id, $type, $hash)
            ?? $this->generate->handle($accessTokenId, $principle->id, $type, $now)->data;

        if (! $exercise instanceof PrincipleExercise || $exercise->status->value !== 'ready') {
            $composition[$index] = [...$item, 'type' => $this->offlineSubstitute($type)->value, 'substituted' => true];
            $session->update(['composition' => $composition]);

            return Outcome::noViewMessage(data: ['substituted' => true]);
        }

        $exercise->update(['times_served' => $exercise->times_served + 1, 'last_served_at' => $now]);
        $composition[$index] = [...$item, 'exercise_id' => $exercise->id];
        $session->update(['composition' => $composition]);

        $this->replenish($accessTokenId, $principle->id, $type, $hash);

        return Outcome::noViewMessage(data: ['substituted' => false]);
    }

    private function offlineSubstitute(ExerciseType $type): ExerciseType
    {
        return $type === ExerciseType::ApplyScenario ? ExerciseType::CreateExample : ExerciseType::RecallTitleToStatement;
    }

    /** Se o estoque deste tipo caiu abaixo do mínimo, pede reposição em segundo plano. */
    private function replenish(int $accessTokenId, int $principleId, ExerciseType $type, string $hash): void
    {
        $stock = PrincipleExercise::query()
            ->where('access_token_id', $accessTokenId)->where('principle_id', $principleId)
            ->where('type', $type)->where('status', 'ready')->where('source_hash', $hash)->where('prompt_version', PromptVersions::for($type))
            ->where('times_served', 0)->count();

        if ($stock < (int) config('principios.ai.stock_per_type')) {
            $this->dispatch->handle($accessTokenId, $principleId, $type, $hash);
        }
    }
}
