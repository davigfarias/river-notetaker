<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PrincipleReviewState;
use Carbon\CarbonImmutable;

/**
 * Quantos princípios entram numa sessão de hoje: vencidos + novos dentro do limite diário,
 * contando só o recorte escolhido (disciplina e/ou categoria), se houver.
 */
final readonly class CountPrinciplesToStudy
{
    public function __construct(private ScopePrinciples $scope) {}

    public function handle(int $accessTokenId, CarbonImmutable $now, ?int $disciplineId = null, ?int $categoryId = null): int
    {
        $states = PrincipleReviewState::query()->where('access_token_id', $accessTokenId);
        $withState = (clone $states)->pluck('principle_id')->all();

        $inScope = fn () => $this->scope->handle($disciplineId, $categoryId);

        $due = $inScope()->whereIn('id', (clone $states)->where('due_at', '<=', $now)->pluck('principle_id')->all())->count();
        $newAvailable = $inScope()->whereNotIn('id', $withState)->count();
        $newToday = (clone $states)->where('created_at', '>=', $now->startOfDay())->count();
        $newCap = max(0, (int) config('principios.session.max_new_per_day') - $newToday);

        return $due + min($newAvailable, $newCap);
    }
}
