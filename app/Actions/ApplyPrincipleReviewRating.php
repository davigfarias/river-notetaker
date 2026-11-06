<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SelfRating;
use App\Models\PrincipleReviewState;
use App\Support\Sm2Scheduler;
use Carbon\CarbonImmutable;

final readonly class ApplyPrincipleReviewRating
{
    public function __construct(private Sm2Scheduler $scheduler) {}

    public function handle(int $accessTokenId, int $principleId, SelfRating $rating, CarbonImmutable $now): PrincipleReviewState
    {
        $current = PrincipleReviewState::query()
            ->where('access_token_id', $accessTokenId)
            ->where('principle_id', $principleId)
            ->first();

        $next = $this->scheduler->next($current?->toSnapshot() ?? $this->scheduler->initial(), $rating, $now);

        return PrincipleReviewState::query()->updateOrCreate(
            ['access_token_id' => $accessTokenId, 'principle_id' => $principleId],
            [
                'due_at' => $next->dueAt,
                'last_reviewed_at' => $next->lastReviewedAt,
                'interval_days' => $next->intervalDays,
                'ease' => $next->ease,
                'repetitions' => $next->repetitions,
                'lapses' => $next->lapses,
                'last_rating' => $rating,
            ],
        );
    }
}
