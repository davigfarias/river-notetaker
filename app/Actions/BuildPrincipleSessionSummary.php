<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\PrincipleSessionSummary;
use App\Enums\SelfRating;
use App\Models\PrincipleAttempt;
use App\Models\PrincipleReviewState;
use Carbon\CarbonImmutable;

final readonly class BuildPrincipleSessionSummary
{
    public function handle(int $accessTokenId, int $sessionId): PrincipleSessionSummary
    {
        $attempts = PrincipleAttempt::query()
            ->where('access_token_id', $accessTokenId)
            ->where('principle_session_id', $sessionId)
            ->whereNotNull('self_rating')
            ->get();

        $nextDue = PrincipleReviewState::query()
            ->where('access_token_id', $accessTokenId)
            ->whereIn('principle_id', $attempts->pluck('principle_id')->unique()->all())
            ->min('due_at');

        return new PrincipleSessionSummary(
            answered: $attempts->count(),
            byLevel: $attempts->countBy(fn (PrincipleAttempt $a): string => $a->bloom_level->label())->all(),
            totalSeconds: (int) round($attempts->sum('duration_ms') / 1000),
            missedPrincipleIds: $attempts->where('self_rating', SelfRating::Again)->map(fn (PrincipleAttempt $a): int => $a->principle_id)->unique()->values()->all(),
            nextDueAt: $nextDue !== null ? CarbonImmutable::parse($nextDue) : null,
        );
    }
}
