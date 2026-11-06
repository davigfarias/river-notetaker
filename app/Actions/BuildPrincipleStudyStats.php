<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\PrincipleStudyStats;
use App\Enums\BloomLevel;
use App\Enums\SelfRating;
use App\Models\PrincipleAttempt;
use Carbon\CarbonImmutable;

/**
 * Taxa de acerto por nível de Bloom e concordância IA x usuário nos últimos dias.
 * A concordância mostra se a IA está permissiva (sugere mais que você escolhe) ou rígida demais.
 */
final readonly class BuildPrincipleStudyStats
{
    public function handle(int $accessTokenId, CarbonImmutable $now, int $days = 30): PrincipleStudyStats
    {
        $attempts = PrincipleAttempt::query()
            ->where('access_token_id', $accessTokenId)
            ->whereNotNull('self_rating')
            ->where('answered_at', '>=', $now->subDays($days))
            ->get(['bloom_level', 'self_rating', 'ai_suggested_rating']);

        $levels = collect(BloomLevel::cases())->mapWithKeys(function (BloomLevel $level) use ($attempts): array {
            $mine = $attempts->where('bloom_level', $level);
            $good = $mine->filter(fn (PrincipleAttempt $a): bool => in_array($a->self_rating, [SelfRating::Good, SelfRating::Easy], true))->count();

            return [$level->label() => ['answered' => $mine->count(), 'goodRate' => $mine->isEmpty() ? null : (float) ($good / $mine->count())]];
        })->all();

        $compared = $attempts->filter(fn (PrincipleAttempt $a): bool => $a->ai_suggested_rating !== null);
        $score = fn (PrincipleAttempt $a, int $sign): bool => $sign * ($a->ai_suggested_rating->score() <=> $a->self_rating->score()) > 0;

        return new PrincipleStudyStats(
            answered: $attempts->count(),
            levels: $levels,
            agreement: $compared->isEmpty() ? null : [
                'compared' => $compared->count(),
                'same' => $compared->filter(fn (PrincipleAttempt $a): bool => $a->ai_suggested_rating === $a->self_rating)->count(),
                'aiHigher' => $compared->filter(fn (PrincipleAttempt $a): bool => $score($a, 1))->count(),
                'aiLower' => $compared->filter(fn (PrincipleAttempt $a): bool => $score($a, -1))->count(),
            ],
        );
    }
}
