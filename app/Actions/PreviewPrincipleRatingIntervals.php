<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SelfRating;
use App\Models\PrincipleReviewState;
use App\Support\Sm2Scheduler;
use Carbon\CarbonImmutable;

/**
 * Em quantos dias o princípio volta para cada nota ("volta em 3 dias" nos botões).
 */
final readonly class PreviewPrincipleRatingIntervals
{
    public function __construct(private Sm2Scheduler $scheduler) {}

    /**
     * @return array<string, int> valor do SelfRating => dias
     */
    public function handle(int $accessTokenId, int $principleId, CarbonImmutable $now): array
    {
        $snapshot = PrincipleReviewState::query()
            ->where('access_token_id', $accessTokenId)
            ->where('principle_id', $principleId)
            ->first()?->toSnapshot() ?? $this->scheduler->initial();

        return collect(SelfRating::cases())
            ->mapWithKeys(fn (SelfRating $r): array => [$r->value => $this->scheduler->next($snapshot, $r, $now)->intervalDays])
            ->all();
    }
}
