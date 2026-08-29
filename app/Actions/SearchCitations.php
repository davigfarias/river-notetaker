<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Citation;
use App\Support\Outcome;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

final readonly class SearchCitations
{
    public function handle(string $term, int $accessTokenId, int $perPage = 15): Outcome
    {
        try {
            $term = trim($term);

            if ($term === '') {
                return Outcome::noViewMessage(data: new LengthAwarePaginator(collect(), 0, $perPage));
            }

            $citations = Citation::search($term)
                ->where('access_token_id', $accessTokenId)
                ->query(fn ($query) => $query->with('referenceMaterial'))
                ->paginate($perPage);

            return Outcome::noViewMessage(data: $citations);
        } catch (\Exception $e) {
            Log::error("Erro ao pesquisar citações: {$e->getMessage()}");

            return Outcome::failure(
                message: 'Não foi possível pesquisar as citações.',
                data: new LengthAwarePaginator(collect(), 0, $perPage),
            );
        }
    }
}
