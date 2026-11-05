<?php

use App\Actions\CalculateConceptRecallCounters;
use App\Enums\ConceptReviewResult;

test('remembering adds to the aced count only', function () {
    $counters = (new CalculateConceptRecallCounters)->handle(2, 3, ConceptReviewResult::Remembered);

    expect($counters->acedCount)->toBe(3)->and($counters->missedCount)->toBe(3);
});

test('forgetting adds to the missed count only', function () {
    $counters = (new CalculateConceptRecallCounters)->handle(2, 3, ConceptReviewResult::Forgot);

    expect($counters->acedCount)->toBe(2)->and($counters->missedCount)->toBe(4);
});
