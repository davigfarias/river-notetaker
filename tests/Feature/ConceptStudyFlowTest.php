<?php

use App\Models\AccessToken;
use App\Models\ConceptDeck;
use App\Models\ConceptDeckCard;
use App\Models\ConceptRecallCounter;
use App\Models\ConceptReview;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();

    $this->deck = ConceptDeck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Soteriologia']);
    $this->first = ConceptDeckCard::factory()->create(['concept_deck_id' => $this->deck->id]);
    $this->second = ConceptDeckCard::factory()->create(['concept_deck_id' => $this->deck->id]);
});

function study(ConceptDeck $deck)
{
    return Livewire::test('pages::estudar-conceitos', ['deck' => $deck->id]);
}

test('remembering updates the counter and records a review with the token', function () {
    study($this->deck)
        ->call('reveal')
        ->call('answer', 'remembered')
        ->assertSet('completedCount', 1)
        ->assertSet('pendingAdvance', true)
        ->assertSet('revealed', false);

    $counter = ConceptRecallCounter::where('concept_id', $this->first->concept_id)->first();

    expect($counter->access_token_id)->toBe($this->token->id)
        ->and($counter->aced_count)->toBe(1)
        ->and($counter->missed_count)->toBe(0)
        ->and($counter->last_reviewed_at)->not->toBeNull()
        ->and(ConceptReview::where('access_token_id', $this->token->id)->where('concept_id', $this->first->concept_id)->count())->toBe(1);
});

test('forgetting requeues the card at the end without counting it as completed', function () {
    study($this->deck)
        ->call('reveal')
        ->call('answer', 'forgot')
        ->assertSet('completedCount', 0)
        ->assertSet('cardIds', [$this->first->id, $this->second->id, $this->first->id])
        ->assertSet('totalCards', 2);

    expect(ConceptRecallCounter::where('concept_id', $this->first->concept_id)->value('missed_count'))->toBe(1);
});

test('answering without revealing, with a bad value, or twice records nothing extra', function () {
    $page = study($this->deck)->call('answer', 'remembered');

    expect(ConceptReview::count())->toBe(0);

    $page->call('reveal')->call('answer', 'banana');
    expect(ConceptReview::count())->toBe(0);

    $page->call('answer', 'remembered')->call('answer', 'remembered');
    expect(ConceptReview::count())->toBe(1);
});

test('advance only moves once after an answer', function () {
    study($this->deck)
        ->call('advance')
        ->assertSet('index', 0)
        ->call('reveal')
        ->call('answer', 'remembered')
        ->call('advance')
        ->call('advance')
        ->assertSet('index', 1);
});

test('undo restores counters, removes the review and rewinds the queue', function () {
    $page = study($this->deck)->call('reveal')->call('answer', 'forgot')->call('advance');

    $page->call('goBack')
        ->assertSet('cardIds', [$this->first->id, $this->second->id])
        ->assertSet('index', 0)
        ->assertSet('revealed', true)
        ->assertSet('history', []);

    $counter = ConceptRecallCounter::where('concept_id', $this->first->concept_id)->first();

    expect($counter->missed_count)->toBe(0)->and($counter->last_reviewed_at)->toBeNull()
        ->and(ConceptReview::count())->toBe(0);

    $page->call('answer', 'remembered')->call('advance')->call('goBack')->assertSet('completedCount', 0);
});

test('counters are shared between decks and isolated between tokens', function () {
    $otherDeck = ConceptDeck::factory()->create(['access_token_id' => $this->token->id]);
    ConceptDeckCard::factory()->create(['concept_deck_id' => $otherDeck->id, 'concept_id' => $this->first->concept_id]);

    study($this->deck)->call('reveal')->call('answer', 'remembered');
    study($otherDeck)->call('reveal')->call('answer', 'remembered');

    expect(ConceptRecallCounter::where('concept_id', $this->first->concept_id)->value('aced_count'))->toBe(2);

    $stranger = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $stranger->id]);
    $strangerDeck = ConceptDeck::factory()->create(['access_token_id' => $stranger->id]);
    ConceptDeckCard::factory()->create(['concept_deck_id' => $strangerDeck->id, 'concept_id' => $this->first->concept_id]);

    study($strangerDeck)->call('reveal')->call('answer', 'forgot');

    expect(ConceptRecallCounter::where('access_token_id', $this->token->id)->where('concept_id', $this->first->concept_id)->value('aced_count'))->toBe(2)
        ->and(ConceptRecallCounter::where('access_token_id', $stranger->id)->where('concept_id', $this->first->concept_id)->value('missed_count'))->toBe(1);
});

test('a card removed from the deck mid-session can be skipped', function () {
    $page = study($this->deck);

    $this->first->delete();

    $page->call('$refresh')
        ->assertSee('Este conceito foi apagado ou saiu do deck.')
        ->call('skipMissing')
        ->assertSet('index', 1)
        ->assertSet('completedCount', 1)
        ->assertDontSee('Este conceito foi apagado');
});

test('empty states and restart', function () {
    $page = study($this->deck)
        ->call('reveal')->call('answer', 'remembered')->call('advance')
        ->call('reveal')->call('answer', 'remembered')->call('advance')
        ->assertSee('Sessão concluída');

    $page->call('restart')->assertSet('index', 0)->assertSet('completedCount', 0)->assertDontSee('Sessão concluída');

    $empty = ConceptDeck::factory()->create(['access_token_id' => $this->token->id]);
    study($empty)->assertSee('Este deck ainda não tem conceitos.');

    $inactive = ConceptDeck::factory()->create(['access_token_id' => $this->token->id]);
    ConceptDeckCard::factory()->inactive()->create(['concept_deck_id' => $inactive->id]);
    study($inactive)->assertSee('Todos os cartões deste deck estão desativados.');
});

test('the star toggles on the current card of the deck', function () {
    study($this->deck)->call('toggleStar');

    expect($this->first->fresh()->is_starred)->toBeTrue();
});

test('the study page renders for the owner', function () {
    $this->get(route('concepts.decks.study', $this->deck->id))->assertOk()->assertSee('Mostrar resposta');
});
