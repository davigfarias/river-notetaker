<?php

use App\Actions\GenerateAccessToken;
use App\Models\Disciplines;
use App\Models\Notes;

function createNoteForBrowserTest(array $overrides = []): array
{
    $result = app(GenerateAccessToken::class)->handle('browser-test')->data;

    $discipline = Disciplines::factory()->create();

    $note = Notes::create([
        'title' => 'Nota original',
        'discipline_id' => $discipline->id,
        'access_token_id' => $result['token']->id,
        'tags' => ['Missões'],
        'impressions' => 'Impressão original.',
        'life_experiences' => 'Experiência original.',
        ...$overrides,
    ]);

    return [$result['plainTextToken'], $discipline, $note];
}

test('title can be edited inline via the pencil modal', function () {
    [$code, $discipline] = createNoteForBrowserTest();

    $page = loginWithAccessToken($code)
        ->navigate("/disciplinas/{$discipline->slug}");

    $page->assertSee('Nota original')
        ->click('[wire\:click="edit(\'title\')"]')
        ->fill('[wire\:model="draft.title"]', 'Título editado')
        ->click('[wire\:click="updateNote(\'title\')"]')
        ->assertSee('Alterações salvas com sucesso.')
        ->assertSee('Título editado');

    expect(Notes::first()->title)->toBe('Título editado');
});

test('a tag pill can be toggled inline with autosave', function () {
    [$code, $discipline] = createNoteForBrowserTest(['tags' => []]);

    $page = loginWithAccessToken($code)
        ->navigate("/disciplinas/{$discipline->slug}");

    $page->click('[wire\:click="toggleTag(\'Missões\')"]')
        ->assertSee('Alterações salvas com sucesso.');

    expect(Notes::first()->tags)->toBe(['Missões']);
});

test('impressions can be edited inline via the EasyMDE modal', function () {
    [$code, $discipline] = createNoteForBrowserTest();

    $page = loginWithAccessToken($code)
        ->navigate("/disciplinas/{$discipline->slug}");

    $page->click('[wire\:click="edit(\'impressions\')"]')
        ->assertSeeIn('.CodeMirror', 'Impressão original.');

    $page->script("document.querySelector('.CodeMirror').CodeMirror.setValue('Impressão editada.')");

    $page->click('[wire\:click="updateNote(\'impressions\')"]')
        ->assertSee('Alterações salvas com sucesso.')
        ->assertSee('Impressão editada.');

    expect(Notes::first()->impressions)->toBe('Impressão editada.');
});

test('life experiences can be edited inline via the EasyMDE modal', function () {
    [$code, $discipline] = createNoteForBrowserTest();

    $page = loginWithAccessToken($code)
        ->navigate("/disciplinas/{$discipline->slug}");

    $page->click('[wire\:click="edit(\'life_experiences\')"]')
        ->assertSeeIn('.CodeMirror', 'Experiência original.');

    $page->script("document.querySelector('.CodeMirror').CodeMirror.setValue('Experiência editada.')");

    $page->click('[wire\:click="updateNote(\'life_experiences\')"]')
        ->assertSee('Alterações salvas com sucesso.')
        ->assertSee('Experiência editada.');

    expect(Notes::first()->life_experiences)->toBe('Experiência editada.');
});
