<?php

declare(strict_types=1);

namespace App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Limite diário de chamadas à IA por token (custo zero extra: ver config principios.ai.daily_limit_per_user).
 * Devolve false quando o limite do dia já foi usado; nesse caso a sessão segue sem IA.
 */
final readonly class ReservePrincipleAiCall
{
    public function handle(int $accessTokenId, CarbonImmutable $now): bool
    {
        $key = "principios:ai:{$accessTokenId}:".$now->toDateString();

        Cache::add($key, 0, $now->endOfDay());

        return Cache::increment($key) <= (int) config('principios.ai.daily_limit_per_user');
    }
}
