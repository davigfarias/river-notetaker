<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\PastoralAdvices;
use Livewire\Livewire;

test('results are grouped by type on the full search page', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();

    Notes::factory()->create(['access_token_id' => $token->id, 'discipline_id' => $discipline->id, 'title' => 'Fé viva']);
    PastoralAdvices::create(['category' => 'Fé', 'advice' => 'Fé sem obras é morta.']);

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::busca', ['q' => 'Fé'])
        ->assertSee('Notas')
        ->assertSee('Fé viva')
        ->assertSee('Conselhos Pastorais')
        ->assertSee('Fé sem obras é morta.');
});

test('an empty query shows the empty state instead of results', function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::busca')
        ->assertSee('Digite um termo pra começar');
});
