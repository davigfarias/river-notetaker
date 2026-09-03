<?php

use App\Models\AccessToken;
use App\Models\Concepts;
use Livewire\Livewire;

beforeEach(function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    Livewire::withoutLazyLoading();
});

test('the recent list shows the latest concepts by default', function () {
    Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido de Deus para com o pecador.']);
    Concepts::create(['term' => 'Fé', 'definition' => 'Confiança que descansa na obra consumada de Cristo.']);

    Livewire::test('pages::concepts')
        ->assertSee('Graça')
        ->assertSee('Fé');
});

test('searching filters the list and clearing it returns to the recents', function () {
    Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido de Deus para com o pecador.']);
    Concepts::create(['term' => 'Justificação', 'definition' => 'Ato pelo qual Deus declara justo o pecador.']);

    $component = Livewire::test('pages::concepts')
        ->set('search', 'Justificação');

    expect($component->get('concepts')->pluck('term')->all())->toContain('Justificação')
        ->and($component->get('concepts')->pluck('term')->all())->not->toContain('Graça');

    $component->set('search', '');

    expect($component->get('concepts')->pluck('term')->all())
        ->toContain('Graça', 'Justificação');
});

test('searching clears a selected letter', function () {
    Concepts::create(['term' => 'Aliança', 'definition' => 'Vínculo soberano que Deus estabelece com o seu povo.']);

    $component = Livewire::test('pages::concepts')
        ->call('selectLetter', 'A')
        ->set('search', 'Aliança');

    expect($component->get('selectedLetter'))->toBeNull();
});

test('a newly added concept appears in the recent list without reloading', function () {
    $component = Livewire::test('pages::concepts')
        ->set('formConcept.term', 'Regeneração')
        ->set('formConcept.definition', 'Obra do Espírito que concede vida espiritual ao pecador.')
        ->call('addSoleConcept')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('concepts', ['term' => 'Regeneração', 'note_id' => null]);

    expect($component->get('concepts')->pluck('term')->all())->toContain('Regeneração');
});
