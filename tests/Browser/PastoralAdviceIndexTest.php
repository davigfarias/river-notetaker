<?php

use App\Actions\GenerateAccessToken;
use App\Models\PastoralAdvices;

test('the pastoral advice index renders themes grouped with quoted advices', function () {
    $result = app(GenerateAccessToken::class)->handle('browser-test')->data;

    PastoralAdvices::create(['category' => 'Ansiedade', 'advice' => 'Lance sobre ele toda a sua ansiedade.']);
    PastoralAdvices::create(['category' => 'Ansiedade', 'advice' => 'Não andeis ansiosos por coisa alguma.']);

    $page = loginWithAccessToken($result['plainTextToken'])
        ->navigate('/conselhos/lista');

    $page->assertSee('Ansiedade')
        ->assertSee('Lance sobre ele toda a sua ansiedade.')
        ->assertSee('Não andeis ansiosos por coisa alguma.');
});

test('the search bar filters advices by theme and by advice text', function () {
    $result = app(GenerateAccessToken::class)->handle('browser-test')->data;

    PastoralAdvices::create(['category' => 'Ansiedade', 'advice' => 'Lance sobre ele toda a sua ansiedade.']);
    PastoralAdvices::create(['category' => 'Casamento', 'advice' => 'Sede um ao outro benignos.']);

    $page = loginWithAccessToken($result['plainTextToken'])
        ->navigate('/conselhos/lista');

    $page->fill('search', 'Casamento')
        ->wait(1)
        ->assertSee('Casamento')
        ->assertDontSee('Ansiedade');

    $page->fill('search', 'benignos')
        ->wait(1)
        ->assertSee('Casamento')
        ->assertDontSee('Ansiedade');
});

test('a sole pastoral advice can be registered without an associated note', function () {
    $result = app(GenerateAccessToken::class)->handle('browser-test')->data;

    $page = loginWithAccessToken($result['plainTextToken'])
        ->navigate('/conselhos/lista');

    $page->click('Adicionar um novo conselho')
        ->fill('[name="category-autocomplete"]', 'Perdão')
        ->fill('[wire\:model="formAdvice.advice"]', 'Perdoai, como também Deus vos perdoou em Cristo.')
        ->wait(0.5)
        ->click('[wire\:click="addSoleAdvice"]')
        ->wait(1)
        ->assertSee('Perdão')
        ->assertSee('Perdoai, como também Deus vos perdoou em Cristo.');

    expect(PastoralAdvices::where('category', 'Perdão')->exists())->toBeTrue();
    expect(PastoralAdvices::where('category', 'Perdão')->first()->note_id)->toBeNull();
});

test('a pastoral advice can be edited inline via the pencil modal', function () {
    $result = app(GenerateAccessToken::class)->handle('browser-test')->data;

    $advice = PastoralAdvices::create(['category' => 'Perseverança', 'advice' => 'Persevera até o fim.']);

    $page = loginWithAccessToken($result['plainTextToken'])
        ->navigate('/conselhos/lista');

    $page->assertSee('Persevera até o fim.')
        ->click('[wire\:click="edit('.$advice->id.')"]')
        ->fill('[wire\:model="editAdviceForm.category"]', 'Perseverança na fé')
        ->fill('[wire\:model="editAdviceForm.advice"]', 'Persevera até o fim e serás salvo.')
        ->click('[wire\:click="updateAdvice"]')
        ->wait(0.5)
        ->assertSee('Conselho pastoral atualizado com sucesso.')
        ->assertSee('Perseverança na fé')
        ->assertSee('Persevera até o fim e serás salvo.');

    expect($advice->fresh()->category)->toBe('Perseverança na fé');
    expect($advice->fresh()->advice)->toBe('Persevera até o fim e serás salvo.');
});
