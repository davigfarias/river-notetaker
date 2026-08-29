<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class GetReferenceMaterials
{
    public function handle(
        int $accessTokenId,
        ?string $filter = null,
        ?string $type = null,
        int $perPage = 12,
    ): Outcome {
        try {
            $materials = ReferenceMaterial::query()
                ->where('access_token_id', $accessTokenId)
                ->withCount('citations')
                ->when(filled($filter), fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query
                        ->whereRaw('LOWER(title) LIKE ?', ['%'.Str::lower((string) $filter).'%'])
                        ->orWhereRaw('LOWER(author) LIKE ?', ['%'.Str::lower((string) $filter).'%'])
                ))
                ->when(filled($type), fn (Builder $query) => $query->where('type', $type))
                ->orderBy('title')
                ->paginate($perPage);

            return Outcome::noViewMessage(data: $materials);
        } catch (\Exception $e) {
            Log::error("Erro ao carregar a biblioteca de referências: {$e->getMessage()}");

            return Outcome::failure(
                message: 'Não foi possível carregar a biblioteca de referências.',
                data: new LengthAwarePaginator(collect(), 0, $perPage),
            );
        }
    }
}
