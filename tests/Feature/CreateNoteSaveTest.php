<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use Livewire\Livewire;

test('saving a new note does not fail validation because of the untouched concept edit form', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::create')
        ->set('notes.title', 'Nota de teste')
        ->set('notes.discipline_id', $discipline->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(Notes::where('title', 'Nota de teste')->exists())->toBeTrue();
});
