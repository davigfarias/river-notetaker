<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use Livewire\Livewire;

test('a successful save dispatches note-draft-saved so the client clears the draft', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::create', ['slug' => $discipline->slug])
        ->set('notes.title', 'Nota de teste')
        ->set('notes.impressions', 'Rascunho de impressões.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('note-draft-saved')
        ->assertRedirect(route('disciplinas.show', ['slug' => $discipline->slug, 'nota' => Notes::firstWhere('title', 'Nota de teste')->id]));
});

test('a failed save keeps the draft by not dispatching note-draft-saved', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::create', ['slug' => $discipline->slug])
        ->set('notes.title', '')
        ->set('notes.impressions', 'Rascunho que não pode ser perdido.')
        ->call('save')
        ->assertHasErrors('notes.title')
        ->assertNotDispatched('note-draft-saved');
});
