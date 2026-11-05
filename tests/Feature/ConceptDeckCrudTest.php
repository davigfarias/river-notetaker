<?php

use App\Actions\DeleteConceptDeck;
use App\Actions\RenameConceptDeck;
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
});

test('a deck is created with a trimmed name', function () {
    Livewire::test('decks-de-conceitos')
        ->set('deckForm.name', '  Soteriologia  ')
        ->call('addDeck')
        ->assertHasNoErrors()
        ->assertSet('deckForm.name', '');

    $this->assertDatabaseHas('concept_decks', ['access_token_id' => $this->token->id, 'name' => 'Soteriologia']);
});

test('invalid deck names show a message in portuguese and create nothing', function (string $name, string $message) {
    ConceptDeck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Patrística']);

    Livewire::test('decks-de-conceitos')
        ->set('deckForm.name', $name)
        ->call('addDeck')
        ->assertHasErrors('deckForm.name')
        ->assertSee($message);

    expect(ConceptDeck::count())->toBe(1);
})->with([
    'blank' => ['   ', 'Digite um nome para o deck.'],
    'too long' => [str_repeat('a', 61), 'no máximo 60 caracteres'],
    'same name ignoring case and accents' => ['PATRISTICA', 'Você já tem um deck chamado'],
]);

test('two owners can use the same deck name', function () {
    ConceptDeck::factory()->create(['name' => 'Reforma']);

    Livewire::test('decks-de-conceitos')
        ->set('deckForm.name', 'Reforma')
        ->call('addDeck')
        ->assertHasNoErrors();

    expect(ConceptDeck::where('name', 'Reforma')->count())->toBe(2);
});

test('the list shows an explicit empty state without decks', function () {
    Livewire::test('decks-de-conceitos')
        ->assertSee('Você ainda não tem decks')
        ->assertSee('Nenhum cartão estudado hoje')
        ->assertSee('—');
});

test('a deck can be renamed but not to another deck name', function () {
    $deck = ConceptDeck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Um']);
    ConceptDeck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Dois']);

    expect(app(RenameConceptDeck::class)->handle($deck->id, 'DOIS', $this->token->id)->success)->toBeFalse()
        ->and(app(RenameConceptDeck::class)->handle($deck->id, ' UM ', $this->token->id)->success)->toBeTrue()
        ->and($deck->fresh()->name)->toBe('UM');
});

test('actions never touch a deck of another token', function () {
    $other = ConceptDeck::factory()->create();

    expect(app(RenameConceptDeck::class)->handle($other->id, 'Novo', $this->token->id)->success)->toBeFalse()
        ->and(app(DeleteConceptDeck::class)->handle($other->id, $this->token->id)->success)->toBeFalse()
        ->and($other->fresh()->name)->not->toBe('Novo');
});

test('deleting a deck keeps concepts, counters and reviews', function () {
    $deck = ConceptDeck::factory()->create(['access_token_id' => $this->token->id]);
    $card = ConceptDeckCard::factory()->create(['concept_deck_id' => $deck->id]);
    ConceptRecallCounter::create(['access_token_id' => $this->token->id, 'concept_id' => $card->concept_id, 'aced_count' => 1]);
    ConceptReview::factory()->create(['access_token_id' => $this->token->id, 'concept_id' => $card->concept_id]);

    expect(app(DeleteConceptDeck::class)->handle($deck->id, $this->token->id)->success)->toBeTrue();

    expect(ConceptDeck::count())->toBe(0)
        ->and(ConceptDeckCard::count())->toBe(0)
        ->and(ConceptRecallCounter::count())->toBe(1)
        ->and(ConceptReview::count())->toBe(1)
        ->and(\App\Models\Concepts::whereKey($card->concept_id)->exists())->toBeTrue();
});
