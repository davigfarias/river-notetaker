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
        ->assertRedirect(route('disciplinas.show', [
            'slug' => $discipline->slug,
            'nota' => Notes::where('title', 'Nota de teste')->where('discipline_id', $discipline->id)->sole()->id,
        ]));
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

test('the discipline page opens the note from the url, also on mobile', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();
    $first = Notes::factory()->create(['discipline_id' => $discipline->id, 'access_token_id' => $token->id]);
    $second = Notes::factory()->create(['discipline_id' => $discipline->id, 'access_token_id' => $token->id]);

    $this->withSession(['access_token_id' => $token->id]);
    Livewire::withoutLazyLoading();

    Livewire::withQueryParams(['nota' => $second->id])
        ->test('pages::disciplina', ['slug' => $discipline->slug])
        ->assertSet('selectedNoteId', $second->id)
        ->assertSet('mobileDetail', true);

    Livewire::withQueryParams(['nota' => 999999])
        ->test('pages::disciplina', ['slug' => $discipline->slug])
        ->assertSet('mobileDetail', false);

    Livewire::test('pages::disciplina', ['slug' => $discipline->slug])
        ->assertSet('mobileDetail', false);

    expect($first->id)->not->toBe($second->id);
});
