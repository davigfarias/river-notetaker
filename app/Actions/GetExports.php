<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Export;
use App\Support\Outcome;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

final readonly class GetExports
{
    public function handle(int $accessTokenId, int $perPage = 15): Outcome
    {
        try {
            $exports = Export::query()
                ->where('access_token_id', $accessTokenId)
                ->with('referenceMaterial:id,title')
                ->latest()
                ->paginate($perPage);

            return Outcome::noViewMessage(data: $exports);
        } catch (\Exception $e) {
            Log::error("Erro ao carregar exportações: {$e->getMessage()}");

            return Outcome::failure(
                message: 'Não foi possível carregar as exportações.',
                data: new LengthAwarePaginator(collect(), 0, $perPage),
            );
        }
    }
}
