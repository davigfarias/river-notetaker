<?php

use App\Actions\GenerateAccessToken;
use App\Models\Concepts;

test('a duplicate concept toast link opens the edit modal without losing the note draft', function () {
    $result = app(GenerateAccessToken::class)->handle('browser-test')->data;

    $concept = Concepts::create([
        'term' => 'Regeneracao',
        'definition' => 'Nascer de novo pela obra do Espirito Santo.',
    ]);

    $page = loginWithAccessToken($result['plainTextToken'])
        ->navigate(route('notas.criar'));

    $page->fill('[wire\:model="notes.title"]', 'Rascunho de teste')
        ->click('[wire\:click="addConcept"]')
        ->fill('[wire\:model="notes.concepts.0.term"]', 'Regeneracao')
        ->wait(1)
        ->assertSee('O conceito já está registrado no sistema!')
        ->assertSee('Editar conceito existente')
        ->click('a[href="#edit-concept-'.$concept->id.'"]')
        ->wait(0.5)
        ->assertValue('[wire\:model="editConceptForm.term"]', 'Regeneracao')
        ->assertValue('[wire\:model="editConceptForm.definition"]', 'Nascer de novo pela obra do Espirito Santo.')
        ->fill('[wire\:model="editConceptForm.term"]', 'Regeneracao (novo nascimento)')
        ->fill('[wire\:model="editConceptForm.definition"]', 'Obra soberana do Espirito Santo que da vida espiritual.')
        ->click('[wire\:click="updateConcept"]')
        ->assertSee("Conceito 'Regeneracao (novo nascimento)' atualizado com sucesso.")
        ->assertValue('[wire\:model="notes.title"]', 'Rascunho de teste');

    expect($concept->fresh()->term)->toBe('Regeneracao (novo nascimento)');
    expect($concept->fresh()->definition)->toBe('Obra soberana do Espirito Santo que da vida espiritual.');
});
