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

test('a note id in the url preselects that note over the first one', function () {
    $token = AccessToken::factory()->create();
    $first = Notes::factory()->create(['access_token_id' => $token->id, 'title' => 'Primeira nota']);
    $discipline = $first->discipline;
    $second = Notes::create(['access_token_id' => $token->id, 'discipline_id' => $discipline->id, 'title' => 'Segunda nota', 'tags' => []]);

    $this->withSession(['access_token_id' => $token->id])
        ->get(route('disciplinas.show', ['slug' => $discipline->slug, 'nota' => $second->id]))
        ->assertOk()
        ->assertSeeText('Segunda nota');
});

test('an invalid note id in the url falls back to the first note of the discipline', function () {
    $token = AccessToken::factory()->create();
    $note = Notes::factory()->create(['access_token_id' => $token->id, 'title' => 'Única nota']);
    $discipline = $note->discipline;

    $this->withSession(['access_token_id' => $token->id])
        ->get(route('disciplinas.show', ['slug' => $discipline->slug, 'nota' => 999999]))
        ->assertOk()
        ->assertSeeText('Única nota');
});
