<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\DispatchExerciseStock;
use App\Actions\FindNeighborPrinciples;
use App\Actions\LoadPrincipleLevelStats;
use App\Actions\SelectSessionPrinciples;
use App\DTO\SessionItem;
use App\DTO\StudyPrinciple;
use App\Enums\ExerciseType;
use App\Models\Principle;
use App\Models\PrincipleReviewState;
use App\Models\PrincipleSession;
use App\Support\Exercises\ExerciseStrategies;
use App\Support\ExerciseTypeChooser;
use App\Support\Outcome;
use App\Support\SessionComposer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class StartPrincipleSessionOrchestrator
{
    public function __construct(
        private SelectSessionPrinciples $select,
        private LoadPrincipleLevelStats $loadStats,
        private ExerciseTypeChooser $chooser,
        private SessionComposer $composer,
        private ExerciseStrategies $strategies,
        private FindNeighborPrinciples $neighbors,
        private DispatchExerciseStock $dispatchStock,
    ) {}

    /**
     * @param  list<int>|null  $onlyPrincipleIds
     */
    public function handle(
        int $accessTokenId,
        ?int $disciplineId = null,
        ?int $categoryId = null,
        bool $includeNotDue = false,
        ?array $onlyPrincipleIds = null,
        ?CarbonImmutable $now = null,
    ): Outcome {
        try {
            $now ??= CarbonImmutable::now();

            $principles = $this->select->handle($accessTokenId, $disciplineId, $categoryId, $includeNotDue, $onlyPrincipleIds, $now);

            if ($principles->isEmpty()) {
                return Outcome::failure(message: 'Não há princípios para estudar agora.');
            }

            $ids = $principles->map(fn (Principle $p): int => $p->id)->values()->all();
            $states = PrincipleReviewState::query()
                ->where('access_token_id', $accessTokenId)
                ->whereIn('principle_id', $ids)
                ->get()->keyBy('principle_id');
            $stats = $this->loadStats->handle($accessTokenId, $ids);
            $available = $this->strategies->availableTypes((bool) config('principios.ai.enabled'));
            $higherOrderTarget = (int) ceil((float) config('principios.session.min_higher_order_ratio') * $principles->count());

            $items = $principles->values()->map(fn (Principle $p, int $i): SessionItem => new SessionItem(
                principleId: $p->id,
                type: $this->chooser->choose(
                    $this->availableFor($p, $available),
                    $stats[$p->id] ?? [],
                    isNew: ! $states->has($p->id),
                    lapses: $states->get($p->id)->lapses ?? 0,
                    needHigherOrder: $i < $higherOrderTarget,
                ),
                groupKey: StudyPrinciple::groupKeyFor($p),
            ))->values()->all();

            $composition = $this->composer->compose($items);

            $session = PrincipleSession::query()->create([
                'access_token_id' => $accessTokenId,
                'discipline_id' => $disciplineId,
                'principle_category_id' => $categoryId,
                'planned_items' => count($composition),
                'started_at' => $now,
                'composition' => array_map(fn (SessionItem $i): array => $i->toComposition(), $composition),
            ]);

            $this->preGenerate($principles->keyBy('id'), $composition, $accessTokenId);

            return Outcome::success(data: $session);
        } catch (\Exception $e) {
            Log::error("Erro ao iniciar sessão de princípios: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível iniciar a sessão. Tente de novo.');
        }
    }

    /**
     * "Distinguir" precisa de pelo menos um princípio vizinho; sem ele, o tipo sai do sorteio.
     *
     * @param  list<ExerciseType>  $available
     * @return list<ExerciseType>
     */
    private function availableFor(Principle $principle, array $available): array
    {
        if (! in_array(ExerciseType::DistinguishPrinciples, $available, true) || $this->neighbors->handle($principle->id, 1)->isNotEmpty()) {
            return $available;
        }

        return array_values(array_filter($available, fn (ExerciseType $t): bool => $t !== ExerciseType::DistinguishPrinciples));
    }

    /**
     * Pré-geração: pede ao fundo o estoque dos itens que dependem de IA (só se houver fila de verdade).
     *
     * @param  Collection<int, Principle>  $principles
     * @param  array<int, SessionItem>  $composition
     */
    private function preGenerate(Collection $principles, array $composition, int $accessTokenId): void
    {
        foreach ($composition as $item) {
            if ($item->type->requiresAiGeneration() && $principles->has($item->principleId)) {
                $this->dispatchStock->handle($accessTokenId, $item->principleId, $item->type, StudyPrinciple::from($principles[$item->principleId])->sourceHash());
            }
        }
    }
}
