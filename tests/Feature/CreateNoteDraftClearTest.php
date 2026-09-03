<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use Livewire\Livewire;

test('a successful save dispatches note-draft-saved so the client clears the draft', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::create')
        ->set('notes.title', 'Nota de teste')
        ->set('notes.discipline_id', $discipline->id)
        ->set('notes.impressions', 'Rascunho de impressões.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('note-draft-saved')
        ->assertRedirect(route('dashboard'));
});

test('a failed save keeps the draft by not dispatching note-draft-saved', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::create')
        ->set('notes.title', 'Nota sem disciplina')
        ->set('notes.impressions', 'Rascunho que não pode ser perdido.')
        ->call('save')
        ->assertHasErrors('notes.discipline_id')
        ->assertNotDispatched('note-draft-saved');
});
