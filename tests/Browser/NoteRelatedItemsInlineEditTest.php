<?php

use App\Actions\GenerateAccessToken;
use App\Models\Concepts;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\PastoralAdvices;

function createNoteWithRelatedItems(): array
{
    $result = app(GenerateAccessToken::class)->handle('browser-test')->data;

    $discipline = Disciplines::factory()->create();

    $note = Notes::create([
        'title' => 'Nota original',
        'discipline_id' => $discipline->id,
        'access_token_id' => $result['token']->id,
    ]);

    $concept = Concepts::create([
        'note_id' => $note->id,
        'term' => 'Graça',
        'definition' => 'Favor imerecido de Deus para com o pecador.',
    ]);

    $advice = PastoralAdvices::create([
        'note_id' => $note->id,
        'category' => 'Aconselhamento',
        'advice' => 'Ouça antes de responder.',
    ]);

    return [$result['plainTextToken'], $discipline, $concept, $advice];
}

test('a concept inside a note can be edited inline via the pencil modal', function () {
    [$code, $discipline, $concept] = createNoteWithRelatedItems();

    $page = loginWithAccessToken($code)
        ->navigate("/disciplinas/{$discipline->slug}");

    $page->assertSee('Graça')
        ->click('[wire\:click="editConcept('.$concept->id.')"]')
        ->fill('[wire\:model="editConceptForm.term"]', 'Graça comum')
        ->fill('[wire\:model="editConceptForm.definition"]', 'Favor de Deus estendido a toda a humanidade.')
        ->click('[wire\:click="updateConcept"]')
        ->assertSee("Conceito 'Graça comum' atualizado com sucesso.")
        ->assertSee('Graça comum');

    expect($concept->fresh()->term)->toBe('Graça comum');
});

test('a pastoral advice inside a note can be edited inline via the pencil modal', function () {
    [$code, $discipline, $concept, $advice] = createNoteWithRelatedItems();

    $page = loginWithAccessToken($code)
        ->navigate("/disciplinas/{$discipline->slug}");

    $page->assertSee('Aconselhamento')
        ->click('[wire\:click="editAdvice('.$advice->id.')"]')
        ->fill('[wire\:model="editAdviceForm.category"]', 'Escuta ativa')
        ->fill('[wire\:model="editAdviceForm.advice"]', 'Ouça com atenção antes de aconselhar.')
        ->click('[wire\:click="updateAdvice"]')
        ->assertSee('Conselho pastoral atualizado com sucesso.')
        ->assertSee('Escuta ativa');

    expect($advice->fresh()->category)->toBe('Escuta ativa');
});
