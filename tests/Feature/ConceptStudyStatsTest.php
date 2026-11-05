<?php

use App\Actions\ComputeConceptActivityChart;
use App\Actions\ComputeConceptStudyStreak;
use App\Actions\ComputeConceptTodayRecall;
use App\Actions\CountConceptsToReinforce;
use App\Actions\GetConceptDecksSummary;
use App\Enums\ConceptActivityRange;
use App\Enums\ConceptReviewResult;
use App\Models\AccessToken;
use App\Models\ConceptDeck;
use App\Models\ConceptDeckCard;
use App\Models\ConceptRecallCounter;
use App\Models\ConceptReview;
use Carbon\CarbonImmutable;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-05 15:00:00');
    $this->token = AccessToken::factory()->create();
});

afterEach(fn () => CarbonImmutable::setTestNow());

function reviewAt(int $tokenId, string $when, ConceptReviewResult $result = ConceptReviewResult::Remembered): void
{
    ConceptReview::factory()->create(['access_token_id' => $tokenId, 'reviewed_at' => $when, 'result' => $result]);
}

test('today recall is null when nothing was studied', function () {
    $recall = app(ComputeConceptTodayRecall::class)->handle($this->token->id, CarbonImmutable::now());

    expect($recall)->toBe(['reviewedToday' => 0, 'rememberedPercent' => null]);
});

test('today recall is the share of remembered answers, only for the token and only today', function () {
    reviewAt($this->token->id, '2026-11-05 09:00:00');
    reviewAt($this->token->id, '2026-11-05 10:00:00');
    reviewAt($this->token->id, '2026-11-05 11:00:00', ConceptReviewResult::Forgot);
    reviewAt($this->token->id, '2026-11-04 11:00:00', ConceptReviewResult::Forgot);
    reviewAt(AccessToken::factory()->create()->id, '2026-11-05 11:00:00', ConceptReviewResult::Forgot);

    $recall = app(ComputeConceptTodayRecall::class)->handle($this->token->id, CarbonImmutable::now());

    expect($recall)->toBe(['reviewedToday' => 3, 'rememberedPercent' => 67]);
});

test('streak counts consecutive days and marks the last 7', function () {
    reviewAt($this->token->id, '2026-11-05 09:00:00');
    reviewAt($this->token->id, '2026-11-04 09:00:00');
    reviewAt($this->token->id, '2026-11-03 09:00:00');
    reviewAt($this->token->id, '2026-11-01 09:00:00');

    $streak = app(ComputeConceptStudyStreak::class)->handle($this->token->id, CarbonImmutable::now());

    expect($streak->days)->toBe(3)
        ->and($streak->last7)->toBe([false, false, true, false, true, true, true]);
});

test('streak survives a day not studied yet today', function () {
    reviewAt($this->token->id, '2026-11-04 09:00:00');

    $streak = app(ComputeConceptStudyStreak::class)->handle($this->token->id, CarbonImmutable::now());

    expect($streak->days)->toBe(1)->and($streak->last7[6])->toBeFalse();
});

test('activity chart counts reviews per bucket and scales percentages', function () {
    reviewAt($this->token->id, '2026-11-05 09:00:00');
    reviewAt($this->token->id, '2026-11-05 10:00:00');
    reviewAt($this->token->id, '2026-11-04 10:00:00');
    reviewAt(AccessToken::factory()->create()->id, '2026-11-05 10:00:00');

    $week = app(ComputeConceptActivityChart::class)->handle($this->token->id, ConceptActivityRange::Week, CarbonImmutable::now());

    expect($week)->toHaveCount(7)
        ->and(array_column($week, 'count'))->toBe([0, 0, 0, 0, 0, 1, 2])
        ->and(array_column($week, 'percent'))->toBe([0, 0, 0, 0, 0, 50, 100]);

    $month = app(ComputeConceptActivityChart::class)->handle($this->token->id, ConceptActivityRange::Month, CarbonImmutable::now());
    $all = app(ComputeConceptActivityChart::class)->handle($this->token->id, ConceptActivityRange::All, CarbonImmutable::now());

    expect(array_sum(array_column($month, 'count')))->toBe(3)->and($month)->toHaveCount(4)
        ->and(array_sum(array_column($all, 'count')))->toBe(3)->and($all)->toHaveCount(6);
});

test('to reinforce counts a concept once per token even when it is in two decks', function () {
    $deckA = ConceptDeck::factory()->create(['access_token_id' => $this->token->id]);
    $deckB = ConceptDeck::factory()->create(['access_token_id' => $this->token->id]);
    $cardA = ConceptDeckCard::factory()->create(['concept_deck_id' => $deckA->id]);
    ConceptDeckCard::factory()->create(['concept_deck_id' => $deckB->id, 'concept_id' => $cardA->concept_id]);
    $inactive = ConceptDeckCard::factory()->inactive()->create(['concept_deck_id' => $deckA->id]);
    $notInDeck = ConceptDeckCard::factory()->create();

    foreach ([$cardA->concept_id, $inactive->concept_id, $notInDeck->concept_id] as $conceptId) {
        ConceptRecallCounter::create(['access_token_id' => $this->token->id, 'concept_id' => $conceptId, 'missed_count' => 2, 'aced_count' => 1]);
    }
    ConceptRecallCounter::create(['access_token_id' => AccessToken::factory()->create()->id, 'concept_id' => $cardA->concept_id, 'missed_count' => 5]);

    expect(app(CountConceptsToReinforce::class)->handle($this->token->id))->toBe(1);
});

test('decks summary shows status, counts and orders by last review', function () {
    $stale = ConceptDeck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Parado']);
    $studied = ConceptDeck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Estudado']);
    $hard = ConceptDeck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Difícil']);
    ConceptDeck::factory()->create(); // other token

    ConceptDeckCard::factory()->create(['concept_deck_id' => $stale->id]);
    $s = ConceptDeckCard::factory()->create(['concept_deck_id' => $studied->id]);
    ConceptDeckCard::factory()->inactive()->create(['concept_deck_id' => $studied->id]);
    $h = ConceptDeckCard::factory()->create(['concept_deck_id' => $hard->id]);

    ConceptRecallCounter::create(['access_token_id' => $this->token->id, 'concept_id' => $s->concept_id, 'aced_count' => 2, 'missed_count' => 0, 'last_reviewed_at' => '2026-11-05 09:00:00']);
    ConceptRecallCounter::create(['access_token_id' => $this->token->id, 'concept_id' => $h->concept_id, 'aced_count' => 0, 'missed_count' => 1, 'last_reviewed_at' => '2026-11-04 09:00:00']);
    // another token's review must not mark "Parado" as reviewed
    ConceptRecallCounter::create(['access_token_id' => AccessToken::factory()->create()->id, 'concept_id' => ConceptDeckCard::where('concept_deck_id', $stale->id)->value('concept_id'), 'aced_count' => 1, 'last_reviewed_at' => '2026-11-05 09:30:00']);

    $summary = app(GetConceptDecksSummary::class)->handle($this->token->id, CarbonImmutable::now());

    expect($summary->pluck('name')->all())->toBe(['Estudado', 'Difícil', 'Parado'])
        ->and($summary->pluck('meta')->all())->toBe(['Revisado hoje', '1 para reforçar', 'Em dia'])
        ->and($summary[0]['cardsCount'])->toBe(2)->and($summary[0]['activeCount'])->toBe(1)
        ->and($summary[1]['dim'])->toBeFalse()->and($summary[1]['urgent'])->toBeFalse()
        ->and($summary[2]['lastReviewedAt'])->toBeNull();
});
