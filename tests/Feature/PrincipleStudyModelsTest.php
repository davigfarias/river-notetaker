<?php

use App\Models\PrincipleAttempt;
use App\Models\PrincipleExercise;
use App\Models\PrincipleReviewState;
use App\Models\PrincipleSession;

test('as factories de estudo de princípios criam registros válidos', function () {
    expect(PrincipleReviewState::factory()->due()->create()->due_at->isPast())->toBeTrue()
        ->and(PrincipleExercise::factory()->stale()->create()->status->value)->toBe('stale')
        ->and(PrincipleSession::factory()->finished()->create()->finished_at)->not->toBeNull()
        ->and(PrincipleAttempt::factory()->create()->self_rating->label())->toBe('Bom');
});
