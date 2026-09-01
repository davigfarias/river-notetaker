<?php

use App\Models\AccessToken;
use App\Models\ReferenceMaterial;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();
});

test('a reference material can be added from the library page', function () {
    Livewire::test('pages::referencias')
        ->set('form.title', 'A Vida Juntos')
        ->set('form.author', 'Dietrich Bonhoeffer')
        ->set('form.type', 'book-open')
        ->set('form.year', 1939)
        ->call('addMaterial')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('reference_materials', [
        'title' => 'A Vida Juntos',
        'author' => 'Dietrich Bonhoeffer',
        'access_token_id' => $this->token->id,
    ]);
});

test('title is required when adding a reference material', function () {
    Livewire::test('pages::referencias')
        ->set('form.title', '')
        ->call('addMaterial')
        ->assertHasErrors('form.title');
});

test('the library only lists materials of the requesting token', function () {
    ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Minha obra']);
    ReferenceMaterial::factory()->create(['access_token_id' => AccessToken::factory()->create()->id, 'title' => 'Obra alheia']);

    Livewire::test('pages::referencias')
        ->assertSee('Minha obra')
        ->assertDontSee('Obra alheia');
});

test('the library can be filtered by type and text', function () {
    ReferenceMaterial::factory()->book()->create(['access_token_id' => $this->token->id, 'title' => 'Livro de Teologia']);
    ReferenceMaterial::factory()->article()->create(['access_token_id' => $this->token->id, 'title' => 'Artigo sobre Graça']);

    Livewire::test('pages::referencias')
        ->set('type', 'newspaper')
        ->assertSee('Artigo sobre Graça')
        ->assertDontSee('Livro de Teologia')
        ->set('type', '')
        ->set('filter', 'Teologia')
        ->assertSee('Livro de Teologia')
        ->assertDontSee('Artigo sobre Graça');
});

test('a reference material can be edited on its detail page', function () {
    $material = ReferenceMaterial::factory()->create([
        'access_token_id' => $this->token->id,
        'title' => 'Título antigo',
    ]);

    Livewire::test('pages::referencia', ['id' => $material->id])
        ->call('openEditMaterial')
        ->set('editForm.title', 'Título novo')
        ->call('updateMaterial')
        ->assertHasNoErrors();

    expect($material->refresh()->title)->toBe('Título novo');
});

test('a token cannot open a reference material it does not own', function () {
    $material = ReferenceMaterial::factory()->create([
        'access_token_id' => AccessToken::factory()->create()->id,
    ]);

    Livewire::test('pages::referencia', ['id' => $material->id])
        ->assertStatus(404);
});
