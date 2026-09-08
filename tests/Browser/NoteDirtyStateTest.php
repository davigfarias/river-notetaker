<?php

use App\Actions\GenerateAccessToken;
use App\Models\Disciplines;

function openCreateNotePage(): object
{
    $code = app(GenerateAccessToken::class)->handle('browser-test')->data['plainTextToken'];
    $discipline = Disciplines::factory()->create();

    return loginWithAccessToken($code)
        ->navigate("/disciplinas/{$discipline->slug}/notas/nova")
        ->assertPresent('[wire\:model="notes.title"]');
}

const BEFORE_UNLOAD_PREVENTED = <<<'JS'
    (() => {
        const e = new Event('beforeunload', { cancelable: true });
        window.dispatchEvent(e);
        return e.defaultPrevented;
    })()
JS;

test('the unsaved changes indicator stays hidden until a field is edited', function () {
    $page = openCreateNotePage();

    $page->assertDontSee('Nessa nota há edições não salvas')
        ->assertScript(BEFORE_UNLOAD_PREVENTED, false);

    $page->fill('[wire\:model="notes.title"]', 'Rascunho de anotação');

    $page->assertSee('Nessa nota há edições não salvas')
        ->assertScript(BEFORE_UNLOAD_PREVENTED, true);
});

test('a committed server action keeps the page marked as dirty', function () {
    $page = openCreateNotePage();

    // wire:click actions round-trip to the server; the native $dirty would reset
    // here, but the client-side snapshot comparison keeps the warning on.
    $page->click('Adicionar Conceito')
        ->wait(0.5)
        ->assertSee('Nessa nota há edições não salvas')
        ->assertScript(BEFORE_UNLOAD_PREVENTED, true);
});
