<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use Livewire\Livewire;

test('saving a new note does not fail validation because of the untouched concept edit form', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::create', ['slug' => $discipline->slug])
        ->set('notes.title', 'Nota de teste')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(Notes::where('title', 'Nota de teste')
        ->where('discipline_id', $discipline->id)
        ->exists())->toBeTrue();
});

test('the discipline is taken from the route slug, not a select', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::create', ['slug' => $discipline->slug])
        ->assertSet('notes.discipline_id', $discipline->id)
        ->assertDontSee('Selecione uma disciplina')
        ->assertSee($discipline->title);
});

test('an unknown discipline slug aborts with 404', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::create', ['slug' => 'disciplina-inexistente'])
        ->assertStatus(404);
});
