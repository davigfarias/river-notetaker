<?php

use App\Actions\GenerateAccessToken;

function loginAndOpenCreatePage(): object
{
    $code = app(GenerateAccessToken::class)->handle('browser-test')->data['plainTextToken'];

    return loginWithAccessToken($code)
        ->navigate('/notas/nova')
        ->assertPresent('.CodeMirror');
}

const DRAFT_KEY_EXISTS = "Object.keys(localStorage).some(k => k.startsWith('smde_note-draft:') && k.includes(':create:impressions'))";

function typeImpressions(object $page, string $text): void
{
    $page->script("document.querySelectorAll('.CodeMirror')[0].CodeMirror.setValue('{$text}')");
}

test('a draft is autosaved to localStorage while typing', function () {
    $page = loginAndOpenCreatePage();
    typeImpressions($page, 'Texto que nao pode ser perdido.');

    $page->wait(3)->assertScript(DRAFT_KEY_EXISTS, true);
});

test('the draft is restored with a banner after a reload', function () {
    $page = loginAndOpenCreatePage();
    typeImpressions($page, 'Impressao recuperavel.');

    $page->wait(3)
        ->refresh()
        ->assertPresent('.CodeMirror')
        ->assertSeeIn('.CodeMirror', 'Impressao recuperavel.')
        ->assertSee('Rascunho recuperado');
});

test('discarding the draft clears the editor, the banner and localStorage', function () {
    $page = loginAndOpenCreatePage();
    typeImpressions($page, 'Rascunho a descartar.');

    $page->wait(3)
        ->refresh()
        ->assertSee('Rascunho recuperado')
        ->click('Descartar')
        ->assertDontSee('Rascunho recuperado')
        ->assertScript(DRAFT_KEY_EXISTS, false)
        ->assertScript("document.querySelectorAll('.CodeMirror')[0].CodeMirror.getValue() === ''", true);
});

test('the note-draft-saved event clears the stored draft', function () {
    $page = loginAndOpenCreatePage();
    typeImpressions($page, 'Rascunho salvo no servidor.');

    $page->wait(3)->assertScript(DRAFT_KEY_EXISTS, true);

    $page->script("window.Livewire.dispatch('note-draft-saved')");

    $page->wait(0.5)->assertScript(DRAFT_KEY_EXISTS, false);
});

test('both editors persist their own draft even when the page dies before the debounce', function () {
    $page = loginAndOpenCreatePage();

    // life experiences typed and the page reloaded right away (inside the debounce window)
    $page->script("document.querySelectorAll('.CodeMirror')[0].CodeMirror.setValue('Impressoes.')");
    $page->script("document.querySelectorAll('.CodeMirror')[1].CodeMirror.setValue('Experiencias de vida.')");
    $page->wait(0.2);

    $page->refresh()
        ->assertPresent('.CodeMirror')
        ->assertSeeIn('.CodeMirror', 'Impressoes.')
        ->assertSeeIn('.CodeMirror', 'Experiencias de vida.');

    expect((bool) $page->script("document.querySelectorAll('.CodeMirror')[1].CodeMirror.getValue().includes('Experiencias de vida.')"))->toBeTrue();
});
