<?php

use App\Actions\GenerateAccessToken;
use App\Models\Concepts;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\PastoralAdvices;

function createEmptyNoteForBrowserTest(): array
{
    $result = app(GenerateAccessToken::class)->handle('browser-test')->data;

    $discipline = Disciplines::factory()->create();

    $note = Notes::create([
        'title' => 'Nota original',
        'discipline_id' => $discipline->id,
        'access_token_id' => $result['token']->id,
    ]);

    return [$result['plainTextToken'], $discipline, $note];
}

test('a concept can be added to a note that has none yet', function () {
    [$code, $discipline, $note] = createEmptyNoteForBrowserTest();

    $page = loginWithAccessToken($code)
        ->navigate("/disciplinas/{$discipline->slug}");

    $page->click('[wire\:click="$set(\'addingConcept\', true)"]')
        ->fill('[wire\:model="addConceptForm.term"]', 'Redenção')
        ->fill('[wire\:model="addConceptForm.definition"]', 'O ato de resgatar através de um preço pago.')
        ->click('[wire\:click="addConcept"]')
        ->assertSee("Conceito 'Redenção' adicionado com sucesso.")
        ->assertSee('Redenção');

    expect(Concepts::where('note_id', $note->id)->where('term', 'Redenção')->exists())->toBeTrue();
});

test('adding a concept with a term that already exists warns the user without blocking', function () {
    [$code, $discipline, $note] = createEmptyNoteForBrowserTest();

    Concepts::create(['note_id' => $note->id, 'term' => 'Graça', 'definition' => 'Favor imerecido.']);

    $page = loginWithAccessToken($code)
        ->navigate("/disciplinas/{$discipline->slug}");

    $page->click('[wire\:click="$set(\'addingConcept\', true)"]')
        ->fill('[wire\:model="addConceptForm.term"]', 'Graça')
        ->assertSee('O conceito já está registrado no sistema!')
        ->fill('[wire\:model="addConceptForm.definition"]', 'Definição alternativa para o mesmo termo.')
        ->click('[wire\:click="addConcept"]')
        ->assertSee("Conceito 'Graça' adicionado com sucesso.");

    expect(Concepts::where('note_id', $note->id)->where('term', 'Graça')->count())->toBe(2);
});

test('a pastoral advice can be added to a note that has none yet', function () {
    [$code, $discipline, $note] = createEmptyNoteForBrowserTest();

    $page = loginWithAccessToken($code)
        ->navigate("/disciplinas/{$discipline->slug}");

    $page->click('[wire\:click="$set(\'addingAdvice\', true)"]')
        ->fill('[wire\:model="addAdviceForm.category"]', 'Luto')
        ->fill('[wire\:model="addAdviceForm.advice"]', 'Esteja presente antes de tentar explicar.')
        ->click('[wire\:click="addAdvice"]')
        ->assertSee('Conselho pastoral adicionado com sucesso.')
        ->assertSee('Luto');

    expect(PastoralAdvices::where('note_id', $note->id)->where('category', 'Luto')->exists())->toBeTrue();
});
