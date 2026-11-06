<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleSession;
use Carbon\CarbonImmutable;

final readonly class FinishPrincipleSession
{
    public function handle(int $accessTokenId, int $sessionId, CarbonImmutable $now): void
    {
        PrincipleSession::query()
            ->where('access_token_id', $accessTokenId)
            ->whereNull('finished_at')
            ->whereKey($sessionId)
            ->update(['finished_at' => $now]);
    }
}
