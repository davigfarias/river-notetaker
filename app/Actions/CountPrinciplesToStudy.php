<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Principle;
use App\Models\PrincipleReviewState;
use Carbon\CarbonImmutable;

/**
 * Quantos princípios entram numa sessão de hoje: vencidos + novos dentro do limite diário.
 */
final readonly class CountPrinciplesToStudy
{
    public function handle(int $accessTokenId, CarbonImmutable $now): int
    {
        $states = PrincipleReviewState::query()->where('access_token_id', $accessTokenId);

        $due = (clone $states)->where('due_at', '<=', $now)->count();
        $newToday = (clone $states)->where('created_at', '>=', $now->startOfDay())->count();
        $newAvailable = max(0, Principle::query()->count() - (clone $states)->count());
        $newCap = max(0, (int) config('principios.session.max_new_per_day') - $newToday);

        return $due + min($newAvailable, $newCap);
    }
}
