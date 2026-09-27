<?php

use App\Models\AccessToken;
use App\Models\Concepts;
use App\Models\Notes;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    Livewire::withoutLazyLoading();

    $this->note = Notes::factory()->create([
        'access_token_id' => $this->token->id,
        'title' => 'Título original',
        'summary' => 'Resumo original.',
    ]);
    $this->discipline = $this->note->discipline;
});

test('title is edited in place and the editor closes on save', function () {
    Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->call('edit', 'title')
        ->assertSet('editing.title', true)
        ->assertSet('draft.title', 'Título original')
        ->set('draft.title', 'Título editado')
        ->call('updateNote', 'title')
        ->assertHasNoErrors()
        ->assertSet('editing.title', false);

    expect($this->note->fresh()->title)->toBe('Título editado');
});

test('cancelling an in-place edit discards the draft', function () {
    Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->call('edit', 'summary')
        ->set('draft.summary', 'Rascunho descartado.')
        ->call('cancelEdit', 'summary')
        ->assertSet('editing.summary', false)
        ->assertSee('Resumo original.');

    expect($this->note->fresh()->summary)->toBe('Resumo original.');
});

test('switching notes closes any open in-place editor', function () {
    $other = Notes::factory()->create([
        'access_token_id' => $this->token->id,
        'discipline_id' => $this->discipline->id,
    ]);

    Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->call('edit', 'summary')
        ->set('editingConceptId', 1)
        ->set('addingAdvice', true)
        ->call('selectNote', $other->id)
        ->assertSet('editing.summary', false)
        ->assertSet('draft', [])
        ->assertSet('editingConceptId', null)
        ->assertSet('addingAdvice', false);
});

test('a concept is edited in place inside its card', function () {
    $concept = Concepts::create([
        'note_id' => $this->note->id,
        'term' => 'Graça',
        'definition' => 'Favor imerecido.',
    ]);

    Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->call('editConcept', $concept->id)
        ->assertSet('editingConceptId', $concept->id)
        ->assertSeeHtml('wire:model="editConceptForm.term"')
        ->set('editConceptForm.definition', 'Favor imerecido de Deus.')
        ->call('updateConcept')
        ->assertHasNoErrors()
        ->assertSet('editingConceptId', null)
        ->assertDontSeeHtml('wire:model="editConceptForm.term"');

    expect($concept->fresh()->definition)->toBe('Favor imerecido de Deus.');
});
