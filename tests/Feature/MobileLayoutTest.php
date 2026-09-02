<?php

use App\Models\AccessToken;
use App\Models\Notes;
use Livewire\Livewire;

test('dashboard renders the mobile bottom navigation links', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/')
        ->assertOk()
        ->assertSee(route('concepts'))
        ->assertSee(route('pastoral'))
        ->assertSee(route('referencias'));
});

test('the desktop navbar and footer are hidden on mobile', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/')
        ->assertOk()
        ->assertSee('max-md:hidden', false)
        ->assertSee('hidden md:flex', false);
});

test('selecting a note on mobile opens the detail panel with a back button', function () {
    $token = AccessToken::factory()->create();
    $note = Notes::factory()->create([
        'access_token_id' => $token->id,
    ]);
    $discipline = $note->discipline;

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::disciplina', ['slug' => $discipline->slug])
        ->assertSet('mobileDetail', false)
        ->call('selectNote', $note->id)
        ->assertSet('mobileDetail', true)
        ->assertSee("\$set('mobileDetail', false)", false);
});
