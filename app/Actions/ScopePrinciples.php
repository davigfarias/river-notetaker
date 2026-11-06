<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Principle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtro de escopo da sessão de estudo: disciplina (pelos temas ligados a ela) e/ou categoria.
 */
final readonly class ScopePrinciples
{
    /**
     * @return Builder<Principle>
     */
    public function handle(?int $disciplineId, ?int $categoryId): Builder
    {
        return Principle::query()
            ->when($disciplineId, fn (Builder $q) => $q->whereHas('principleTopic.disciplines', fn (Builder $d) => $d->where('disciplines.id', $disciplineId)))
            ->when($categoryId, fn (Builder $q) => $q->where('principle_category_id', $categoryId));
    }
}
