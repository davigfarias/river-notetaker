<?php

use App\Models\AccessToken;
use App\Models\ConceptDeck;
use App\Models\ConceptDeckCard;
use App\Models\Concepts;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();

    $this->deck = ConceptDeck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Soteriologia']);
    $this->graca = Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido.']);
    $this->fe = Concepts::create(['term' => 'Fé', 'definition' => 'Confiança em Cristo.']);
});

function builder(ConceptDeck $deck)
{
    return Livewire::test('pages::deck-de-conceitos', ['deck' => $deck->id]);
}

test('bulk add ignores duplicates and invalid ids', function () {
    builder($this->deck)
        ->set('selectedConceptIds', [$this->graca->id, (string) $this->graca->id, 999999, 'abc', $this->fe->id])
        ->call('addSelectedConcepts')
        ->assertSet('selectedConceptIds', []);

    expect($this->deck->cards()->count())->toBe(2);

    builder($this->deck)->set('selectedConceptIds', [$this->graca->id])->call('addSelectedConcepts');

    expect($this->deck->cards()->count())->toBe(2);
});

test('the same concept can be in two decks', function () {
    $second = ConceptDeck::factory()->create(['access_token_id' => $this->token->id]);

    builder($this->deck)->set('selectedConceptIds', [$this->graca->id])->call('addSelectedConcepts');
    builder($second)->set('selectedConceptIds', [$this->graca->id])->call('addSelectedConcepts');

    expect(ConceptDeckCard::where('concept_id', $this->graca->id)->count())->toBe(2);
});

test('cards can be removed, deactivated, reactivated and starred', function () {
    $a = ConceptDeckCard::factory()->create(['concept_deck_id' => $this->deck->id, 'concept_id' => $this->graca->id]);
    $b = ConceptDeckCard::factory()->create(['concept_deck_id' => $this->deck->id, 'concept_id' => $this->fe->id]);

    builder($this->deck)->set('selectedCardIds', [$a->id])->call('deactivateSelectedCards')->assertSet('selectedCardIds', []);
    expect($a->fresh()->is_active)->toBeFalse()->and($b->fresh()->is_active)->toBeTrue();

    builder($this->deck)->set('showInactive', true)->set('selectedCardIds', [$a->id])->call('reactivateSelectedCards');
    expect($a->fresh()->is_active)->toBeTrue();

    builder($this->deck)->call('toggleStar', $b->id)->call('toggleActive', $b->id);
    expect($b->fresh()->is_starred)->toBeTrue()->and($b->fresh()->is_active)->toBeFalse();

    builder($this->deck)->set('selectedCardIds', [$a->id])->call('removeSelectedCards');
    expect(ConceptDeckCard::whereKey($a->id)->exists())->toBeFalse()
        ->and(Concepts::whereKey($this->graca->id)->exists())->toBeTrue();
});

test('card ids from another deck or token are ignored', function () {
    $mine = ConceptDeckCard::factory()->create(['concept_deck_id' => $this->deck->id]);
    $otherDeck = ConceptDeck::factory()->create(['access_token_id' => $this->token->id]);
    $sameTokenOtherDeck = ConceptDeckCard::factory()->create(['concept_deck_id' => $otherDeck->id]);
    $foreign = ConceptDeckCard::factory()->create();

    builder($this->deck)
        ->set('selectedCardIds', [$sameTokenOtherDeck->id, $foreign->id])
        ->call('deactivateSelectedCards')
        ->call('removeSelectedCards')
        ->call('toggleStar', $foreign->id)
        ->call('toggleActive', $foreign->id);

    expect(ConceptDeckCard::count())->toBe(3)
        ->and($sameTokenOtherDeck->fresh()->is_active)->toBeTrue()
        ->and($foreign->fresh()->is_active)->toBeTrue()
        ->and($foreign->fresh()->is_starred)->toBeFalse()
        ->and($mine->fresh()->is_active)->toBeTrue();
});

test('the picker finds concepts by search or letter and marks the ones already in the deck', function () {
    ConceptDeckCard::factory()->create(['concept_deck_id' => $this->deck->id, 'concept_id' => $this->graca->id]);

    builder($this->deck)
        ->set('pickerSearch', 'Graça')
        ->assertSee('Já está no deck');

    builder($this->deck)
        ->call('chooseLetter', 'F')
        ->assertSee('Fé')
        ->assertDontSee('Já está no deck')
        ->call('selectAllListed')
        ->assertSet('selectedConceptIds', [$this->fe->id])
        ->assertSee('Adicionar 1 ao deck');
});

test('select all listed skips concepts already in the deck', function () {
    ConceptDeckCard::factory()->create(['concept_deck_id' => $this->deck->id, 'concept_id' => $this->graca->id]);

    builder($this->deck)
        ->set('pickerSearch', 'a')
        ->call('selectAllListed')
        ->assertSet('selectedConceptIds', fn (array $ids) => ! in_array($this->graca->id, $ids, true));
});

test('empty states are explicit', function () {
    builder($this->deck)
        ->assertSee('Este deck ainda não tem conceitos')
        ->assertSee('Digite um nome na busca ou escolha uma letra')
        ->set('pickerSearch', 'zzzzqq')
        ->assertSee('Nenhum conceito encontrado para')
        ->assertSee('zzzzqq');

    Concepts::query()->delete();

    builder($this->deck)->assertSee('O Dicionário ainda não tem conceitos.');
});

test('a deck of another token is a 404 on show and study', function () {
    $foreign = ConceptDeck::factory()->create();

    $this->get(route('concepts.decks.show', $foreign->id))->assertNotFound();
    $this->get(route('concepts.decks.study', $foreign->id))->assertNotFound();
    $this->get(route('concepts.decks.show', $this->deck->id))->assertOk()->assertSee('Soteriologia');
});

test('the deck can be renamed and deleted from the page', function () {
    builder($this->deck)
        ->set('renameForm.name', 'Outro nome')
        ->call('renameDeck')
        ->assertHasNoErrors();

    expect($this->deck->fresh()->name)->toBe('Outro nome');

    builder($this->deck)->call('deleteDeck')->assertRedirect(route('concepts'));

    expect(ConceptDeck::whereKey($this->deck->id)->exists())->toBeFalse();
});

test('the Conceitos nav item stays active on the deck page', function () {
    $html = $this->get(route('concepts.decks.show', $this->deck->id))->assertOk()->getContent();

    expect($html)->toMatch('/href="[^"]*\/conceitos\/lista"[^>]*data-current/');
});
