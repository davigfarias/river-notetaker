<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleSession;

/**
 * A sessão em andamento mais recente do token, com `answered_count` (itens já avaliados).
 */
final readonly class FindOpenPrincipleSession
{
    public function handle(int $accessTokenId): ?PrincipleSession
    {
        return PrincipleSession::query()
            ->where('access_token_id', $accessTokenId)
            ->whereNull('finished_at')
            ->withCount(['attempts as answered_count' => fn ($q) => $q->whereNotNull('self_rating')])
            ->latest('id')
            ->first();
    }
}
