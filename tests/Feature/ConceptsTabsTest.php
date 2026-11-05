<?php

use App\Models\AccessToken;
use App\Models\Concepts;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();
});

test('the concepts page opens on the Decks tab', function () {
    $this->get(route('concepts'))
        ->assertOk()
        ->assertSee('Decks')
        ->assertSee('Dicionário')
        ->assertSeeLivewire('decks-de-conceitos')
        ->assertDontSeeLivewire('pages::concepts');
});

test('old dictionary links with busca or letra open the Dicionário tab', function (string $query) {
    Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido.']);

    $this->get(route('concepts').$query)
        ->assertOk()
        ->assertSeeLivewire('pages::concepts')
        ->assertDontSeeLivewire('decks-de-conceitos');
})->with(['search' => '?busca=Graça', 'letter' => '?letra=G']);

test('the aba parameter picks the tab and unknown values fall back to Decks', function () {
    $this->get(route('concepts', ['aba' => 'dicionario']))->assertOk()->assertSeeLivewire('pages::concepts');
    $this->get(route('concepts', ['aba' => 'xyz']))->assertOk()->assertSeeLivewire('decks-de-conceitos')->assertDontSeeLivewire('pages::concepts');
});

test('switching the tab swaps the content', function () {
    Livewire::test('pages::conceitos')
        ->assertSet('aba', 'decks')
        ->set('aba', 'dicionario')
        ->assertSeeLivewire('pages::concepts')
        ->assertDontSeeLivewire('decks-de-conceitos');
});
