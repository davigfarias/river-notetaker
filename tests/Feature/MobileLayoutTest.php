<?php

use App\Models\AccessToken;
use App\Models\Notes;
use App\Models\ReadingNote;
use App\Models\ReferenceMaterial;
use Livewire\Livewire;

test('dashboard renders the mobile hamburger menu with the navigation links', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/')
        ->assertOk()
        ->assertSee('data-flux-sidebar-toggle', false)
        ->assertSee('data-flux-sidebar-nav', false)
        ->assertSee('Abrir menu')
        ->assertSee(route('concepts'))
        ->assertSee(route('pastoral'))
        ->assertSee(route('referencias'))
        ->assertDontSee('text-[11px]', false);
});

test('the desktop navbar and footer are hidden on mobile', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/')
        ->assertOk()
        ->assertSee('max-lg:hidden', false)
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

test('review cards are held to the screen width by an explicit column on mobile', function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);
    Livewire::withoutLazyLoading();

    $material = ReferenceMaterial::factory()->create(['access_token_id' => $token->id]);
    ReadingNote::factory()->create([
        'reference_material_id' => $material->id,
        'access_token_id' => $token->id,
        'next_review_at' => now()->subDay(),
    ]);

    Livewire::test('revisoes-da-referencia')
        ->assertSeeHtml('grid grid-cols-1 gap-3 sm:grid-cols-2')
        ->assertSeeHtml('line-clamp-2 break-words');
});
