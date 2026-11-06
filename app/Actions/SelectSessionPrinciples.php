<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Principle;
use App\Models\PrincipleReviewState;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Escolhe os princípios da sessão: vencidos (mais atrasados primeiro), depois novos
 * (até o limite diário) e, se pedido, os que ainda não vencem (vencimento mais próximo).
 */
final readonly class SelectSessionPrinciples
{
    /**
     * @param  list<int>|null  $onlyPrincipleIds  "Revisar só os que errei": ignora agenda e limite de novos.
     * @return Collection<int, Principle>
     */
    public function handle(
        int $accessTokenId,
        ?int $disciplineId,
        ?int $categoryId,
        bool $includeNotDue,
        ?array $onlyPrincipleIds,
        CarbonImmutable $now,
    ): Collection {
        $size = (int) config('principios.session.default_size');

        if ($onlyPrincipleIds !== null) {
            return $this->scoped($disciplineId, $categoryId)->whereIn('id', $onlyPrincipleIds)->limit($size)->get();
        }

        $states = PrincipleReviewState::query()->where('access_token_id', $accessTokenId)->orderBy('due_at')->get();
        $idsOf = fn (Collection $rows): array => $rows->map(fn (PrincipleReviewState $s): int => $s->principle_id)->values()->all();
        $dueIds = $idsOf($states->where('due_at', '<=', $now));
        $upcomingIds = $idsOf($states->where('due_at', '>', $now));

        $due = $this->inOrder($this->scoped($disciplineId, $categoryId)->whereIn('id', $dueIds)->get(), $dueIds)->take($size);

        $newToday = $states->filter(fn (PrincipleReviewState $s): bool => $s->created_at >= $now->startOfDay())->count();
        $newCap = max(0, (int) config('principios.session.max_new_per_day') - $newToday);
        $new = $this->scoped($disciplineId, $categoryId)
            ->whereNotIn('id', $idsOf($states))
            ->orderBy('id')->limit(min($newCap, max(0, $size - $due->count())))->get();

        $picked = $due->concat($new);

        if (! $includeNotDue) {
            return $picked->values();
        }

        $upcoming = $this->inOrder($this->scoped($disciplineId, $categoryId)->whereIn('id', $upcomingIds)->get(), $upcomingIds)
            ->take(max(0, $size - $picked->count()));

        return $picked->concat($upcoming)->values();
    }

    /**
     * @return Builder<Principle>
     */
    private function scoped(?int $disciplineId, ?int $categoryId): Builder
    {
        return Principle::query()
            ->with('concept')
            ->when($disciplineId, fn (Builder $q) => $q->whereHas('principleTopic.disciplines', fn (Builder $d) => $d->where('disciplines.id', $disciplineId)))
            ->when($categoryId, fn (Builder $q) => $q->where('principle_category_id', $categoryId));
    }

    /**
     * @param  Collection<int, Principle>  $principles
     * @param  array<int, int>  $order
     * @return Collection<int, Principle>
     */
    private function inOrder(Collection $principles, array $order): Collection
    {
        return $principles->sortBy(fn (Principle $p): int|false => array_search($p->id, $order, true))->values();
    }
}
