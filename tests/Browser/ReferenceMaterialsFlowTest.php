<?php

use App\Actions\GenerateAccessToken;
use App\Models\AccessToken;
use App\Models\Citation;
use App\Models\Export;
use App\Models\ReferenceMaterial;
use Illuminate\Support\Facades\File;

function browserToken(): array
{
    $plain = app(GenerateAccessToken::class)->handle('browser-test')->data['plainTextToken'];

    return [$plain, AccessToken::firstWhere('name', 'browser-test')];
}

afterEach(function () {
    File::deleteDirectory(storage_path('app/private/exports'));
    File::deleteDirectory(storage_path('app/exports'));
});

test('a new work can be catalogued from the library page', function () {
    [$plain] = browserToken();

    $page = loginWithAccessToken($plain)->navigate('/referencias/lista');

    $page->click('Nova obra')
        ->fill('[wire\:model="form.title"]', 'A Vida Juntos')
        ->fill('[wire\:model="form.author"]', 'Dietrich Bonhoeffer')
        ->wait(0.3)
        ->click('[type="submit"]')
        ->wait(1)
        ->assertSee('A Vida Juntos')
        ->assertSee('Dietrich Bonhoeffer');

    expect(ReferenceMaterial::where('title', 'A Vida Juntos')->exists())->toBeTrue();
});

test('a citation can be added inline on a work detail page', function () {
    [$plain, $token] = browserToken();

    $material = ReferenceMaterial::factory()->create([
        'access_token_id' => $token->id,
        'title' => 'Ortodoxia',
    ]);

    $page = loginWithAccessToken($plain)->navigate('/referencias/'.$material->id);

    $page->assertSee('Ortodoxia')
        ->fill('[wire\:model="citationForm.quote_text"]', 'A tradição é a democracia dos mortos.')
        ->fill('[wire\:model="citationForm.location"]', 'p. 48')
        ->click('Adicionar citação')
        ->wait(1)
        ->assertSee('A tradição é a democracia dos mortos.');

    expect(Citation::where('quote_text', 'A tradição é a democracia dos mortos.')->exists())->toBeTrue();
});

test('the search page finds a citation by its text and links back to the work', function () {
    [$plain, $token] = browserToken();

    $material = ReferenceMaterial::factory()->create([
        'access_token_id' => $token->id,
        'title' => 'Cartas de um Diabo',
    ]);
    Citation::factory()->create([
        'reference_material_id' => $material->id,
        'access_token_id' => $token->id,
        'quote_text' => 'A estrada segura para o Inferno é a gradual.',
    ]);

    $page = loginWithAccessToken($plain)->navigate('/referencias/busca');

    $page->fill('q', 'Inferno')
        ->wait(1)
        ->assertSee('A estrada segura para o Inferno é a gradual.')
        ->assertSee('Cartas de um Diabo');
});

test('exporting a work queues an export visible on the exports page', function () {
    [$plain, $token] = browserToken();

    $material = ReferenceMaterial::factory()->create([
        'access_token_id' => $token->id,
        'title' => 'Milagres',
    ]);
    Citation::factory()->create([
        'reference_material_id' => $material->id,
        'access_token_id' => $token->id,
    ]);

    $page = loginWithAccessToken($plain)->navigate('/referencias/'.$material->id);

    $page->click('Exportar')
        ->wait(0.5)
        ->click('Gerar')
        ->wait(1)
        ->assertSee('Exportação iniciada');

    expect(Export::where('reference_material_id', $material->id)->exists())->toBeTrue();

    $page->navigate('/referencias/exportacoes')
        ->assertSee('Milagres');
});
