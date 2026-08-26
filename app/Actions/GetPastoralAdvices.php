<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\AdvicesDTO;
use App\Models\PastoralAdvices;
use App\Support\Outcome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class GetPastoralAdvices
{
    public function handle(?string $search = null, int $perPage = 5): Outcome
    {
        try {
            $base = PastoralAdvices::query()
                ->when(filled($search), fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query
                        ->whereRaw('LOWER(category) LIKE ?', ['%'.Str::lower($search).'%'])
                        ->orWhereRaw('LOWER(advice) LIKE ?', ['%'.Str::lower($search).'%'])
                ));

            $themes = (clone $base)
                ->selectRaw('MIN(category) as category, LOWER(category) as category_key')
                ->groupByRaw('LOWER(category)')
                ->orderByRaw('LOWER(category)')
                ->paginate($perPage);

            $categoryKeys = $themes->getCollection()->pluck('category_key');

            $advicesByTheme = PastoralAdvices::query()
                ->whereIn(DB::raw('LOWER(category)'), $categoryKeys)
                ->orderBy('id')
                ->get()
                ->groupBy(fn (PastoralAdvices $advice): string => Str::lower($advice->category));

            $themes->setCollection($themes->getCollection()->map(fn ($theme): array => [
                'category' => $theme->category,
                'advices' => $advicesByTheme->get($theme->category_key, collect())
                    ->map(fn (PastoralAdvices $advice): AdvicesDTO => AdvicesDTO::fromModel($advice))
                    ->values(),
            ]));

            return Outcome::noViewMessage(data: $themes);
        } catch (\Exception $e) {
            Log::error("Erro ao carregar os conselhos pastorais: {$e->getMessage()}");

            return Outcome::failure(
                message: 'Não foi possível carregar os conselhos pastorais.',
                data: new LengthAwarePaginator(collect(), 0, $perPage)
            );
        }
    }
}
