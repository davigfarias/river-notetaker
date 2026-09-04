<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use Livewire\Livewire;

test('typing a term populates up to four mixed results', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();

    Notes::factory()->create(['access_token_id' => $token->id, 'discipline_id' => $discipline->id, 'title' => 'Perseverança dos santos']);

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('busca-global')
        ->assertSet('show', false)
        ->call('open')
        ->assertSet('show', true)
        ->set('q', 'Perseverança')
        ->assertSee('Perseverança dos santos');
});

test('closing the modal resets the query', function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('busca-global')
        ->call('open')
        ->set('q', 'algo')
        ->call('close')
        ->assertSet('show', false)
        ->assertSet('q', '');
});
