<?php

use App\Actions\FindConceptCardsToStudy;
use App\Models\AccessToken;
use App\Models\ConceptDeck;
use App\Models\ConceptDeckCard;
use App\Models\ConceptRecallCounter;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = ConceptDeck::factory()->create(['access_token_id' => $this->token->id]);
});

function conceptCounterFor(int $tokenId, ConceptDeckCard $card, int $aced, int $missed, ?string $last = '2026-11-01 10:00:00'): void
{
    ConceptRecallCounter::create([
        'access_token_id' => $tokenId,
        'concept_id' => $card->concept_id,
        'aced_count' => $aced,
        'missed_count' => $missed,
        'last_reviewed_at' => $last,
    ]);
}

test('never reviewed cards are included even without counter rows', function () {
    $cards = ConceptDeckCard::factory()->count(2)->create(['concept_deck_id' => $this->deck->id]);

    $found = app(FindConceptCardsToStudy::class)->handle($this->token->id, $this->deck->id);

    expect($found->pluck('id')->all())->toBe($cards->pluck('id')->all());
});

test('cards are ordered by misses, then never reviewed, then least practised', function () {
    [$missedMost, $missedOnce, $fresh, $seenOften, $seenOnce] = ConceptDeckCard::factory()->count(5)->create(['concept_deck_id' => $this->deck->id])->all();

    conceptCounterFor($this->token->id, $missedMost, 0, 3);
    conceptCounterFor($this->token->id, $missedOnce, 5, 1);
    conceptCounterFor($this->token->id, $seenOften, 9, 0);
    conceptCounterFor($this->token->id, $seenOnce, 1, 0);

    $found = app(FindConceptCardsToStudy::class)->handle($this->token->id, $this->deck->id);

    expect($found->pluck('id')->all())->toBe([
        $missedMost->id, $missedOnce->id, $fresh->id, $seenOnce->id, $seenOften->id,
    ]);
});

test('counters of another token do not affect the order', function () {
    [$a, $b] = ConceptDeckCard::factory()->count(2)->create(['concept_deck_id' => $this->deck->id])->all();
    $other = AccessToken::factory()->create();

    conceptCounterFor($other->id, $b, 0, 9);

    $found = app(FindConceptCardsToStudy::class)->handle($this->token->id, $this->deck->id);

    expect($found->pluck('id')->all())->toBe([$a->id, $b->id]);
});

test('inactive cards and decks of another token are left out', function () {
    ConceptDeckCard::factory()->inactive()->create(['concept_deck_id' => $this->deck->id]);
    $active = ConceptDeckCard::factory()->create(['concept_deck_id' => $this->deck->id]);
    $other = AccessToken::factory()->create();

    expect(app(FindConceptCardsToStudy::class)->handle($this->token->id, $this->deck->id)->pluck('id')->all())->toBe([$active->id])
        ->and(app(FindConceptCardsToStudy::class)->handle($other->id, $this->deck->id))->toBeEmpty();
});

test('the session is limited to 50 cards', function () {
    ConceptDeckCard::factory()->count(55)->create(['concept_deck_id' => $this->deck->id]);

    expect(app(FindConceptCardsToStudy::class)->handle($this->token->id, $this->deck->id))->toHaveCount(50);
});
