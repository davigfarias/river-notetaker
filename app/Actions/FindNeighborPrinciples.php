<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\StudyPrinciple;
use App\Models\Principle;
use Illuminate\Support\Collection;

/**
 * Princípios "vizinhos" para o exercício de distinguir: mesma categoria primeiro, depois o mesmo
 * tema pelos mais próximos na ordem. Determinístico (empate por id).
 */
final readonly class FindNeighborPrinciples
{
    /**
     * @return Collection<int, StudyPrinciple>
     */
    public function handle(int $principleId, int $limit = 3): Collection
    {
        $target = Principle::query()->findOrFail($principleId);

        return Principle::query()
            ->with(['concept', 'category'])
            ->where('principle_topic_id', $target->principle_topic_id)
            ->whereKeyNot($target->id)
            ->get()
            ->sortBy([
                fn (Principle $a, Principle $b): int => (int) ($b->principle_category_id === $target->principle_category_id) <=> (int) ($a->principle_category_id === $target->principle_category_id),
                fn (Principle $a, Principle $b): int => abs($a->position - $target->position) <=> abs($b->position - $target->position),
                fn (Principle $a, Principle $b): int => $a->id <=> $b->id,
            ])
            ->take($limit)
            ->map(fn (Principle $p): StudyPrinciple => StudyPrinciple::from($p))
            ->values();
    }
}
