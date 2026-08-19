<?php

use App\Actions\GenerateAccessToken;
use App\Models\Concepts;
use App\Models\Disciplines;
use App\Models\Notes;

test('a concept can be edited inline via the pencil modal', function () {
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

    $page = loginWithAccessToken($result['plainTextToken'])
        ->navigate('/conceitos/lista');

    $page->assertSee('Graça')
        ->click('[wire\:click="edit('.$concept->id.')"]')
        ->fill('[wire\:model="editConceptForm.term"]', 'Graça comum')
        ->fill('[wire\:model="editConceptForm.definition"]', 'Favor de Deus estendido a toda a humanidade.')
        ->click('[wire\:click="updateConcept"]')
        ->assertSee("Conceito 'Graça comum' atualizado com sucesso.")
        ->assertSee('Graça comum')
        ->assertSee('Favor de Deus estendido a toda a humanidade.');

    expect($concept->fresh()->term)->toBe('Graça comum');
    expect($concept->fresh()->definition)->toBe('Favor de Deus estendido a toda a humanidade.');
});
