<?php

use App\Actions\SearchGlobal;
use App\Enums\SearchResultType;
use App\Models\AccessToken;
use App\Models\ReadingNote;
use App\Models\ReferenceMaterial;
use App\Models\Tags;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    $this->material = ReferenceMaterial::factory()->create([
        'access_token_id' => $this->token->id,
        'current_page' => 87,
    ]);
    Livewire::withoutLazyLoading();
});

test('a reading note can be added with only a body', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('readingNoteForm.body', 'A liberdade aqui não é ausência de lei.')
        ->call('addReadingNote')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('reading_notes', [
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'body' => 'A liberdade aqui não é ausência de lei.',
        'title' => null,
        'location' => null,
    ]);
});

test('the location stays optional and the current page is captured automatically', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('readingNoteForm.body', 'Anotação sem localização alguma.')
        ->call('addReadingNote')
        ->assertHasNoErrors();

    $note = ReadingNote::query()->latest('id')->first();

    expect($note->location)->toBeNull()
        ->and($note->page_snapshot)->toBe(87);
});

test('the body is required', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('readingNoteForm.body', '')
        ->call('addReadingNote')
        ->assertHasErrors('readingNoteForm.body');
});

test('tags can be toggled onto a new reading note', function () {
    Tags::query()->forceCreate(['title' => 'Graça']);

    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('readingNoteForm.body', 'Uma anotação marcada.')
        ->call('toggleReadingNoteTag', 'Graça')
        ->call('addReadingNote')
        ->assertHasNoErrors();

    expect(ReadingNote::query()->latest('id')->first()->tags)->toBe(['Graça']);
});

test('a reading note can be edited and deleted', function () {
    $note = ReadingNote::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'body' => 'Original',
    ]);

    $component = Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('editReadingNote', $note->id)
        ->set('editReadingNoteForm.body', 'Editada')
        ->call('updateReadingNote')
        ->assertHasNoErrors();

    expect($note->refresh()->body)->toBe('Editada');

    $component->call('confirmDeleteReadingNote', $note->id)
        ->call('deleteReadingNote');

    $this->assertDatabaseMissing('reading_notes', ['id' => $note->id]);
});

test('a reading note can be promoted to a citation without being consumed', function () {
    $note = ReadingNote::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'body' => 'Trecho que merece virar citação.',
        'location' => 'p. 42',
    ]);

    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('promoteReadingNote', $note->id);

    $this->assertDatabaseHas('citations', [
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'quote_text' => 'Trecho que merece virar citação.',
        'location' => 'p. 42',
    ]);

    $this->assertDatabaseHas('reading_notes', ['id' => $note->id]);
});

test('the pinned takeaway of a work can be saved and cleared', function () {
    $component = Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('takeaway', 'Livro sobre a diferença entre lei e graça.')
        ->call('saveTakeaway');

    expect($this->material->refresh()->notes_takeaway)->toBe('Livro sobre a diferença entre lei e graça.');

    $component->set('takeaway', '')->call('saveTakeaway');

    expect($this->material->refresh()->notes_takeaway)->toBeNull();
});

test('a token cannot edit or delete a reading note belonging to another token', function () {
    $foreignNote = ReadingNote::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => AccessToken::factory()->create()->id,
        'body' => 'Anotação alheia',
    ]);

    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('confirmDeleteReadingNote', $foreignNote->id)
        ->call('deleteReadingNote');

    $this->assertDatabaseHas('reading_notes', ['id' => $foreignNote->id]);
});

test('reading notes show up in the global search scoped to the token', function () {
    ReadingNote::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'body' => 'A graça precede qualquer esforço.',
    ]);

    ReadingNote::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => AccessToken::factory()->create()->id,
        'body' => 'A graça de outro leitor.',
    ]);

    $results = app(SearchGlobal::class)->handle('graça', $this->token->id)->data;

    $found = $results->where('type', SearchResultType::AnotacaoLeitura);

    expect($found)->toHaveCount(1)
        ->and($found->first()->snippet)->toContain('A graça precede');
});
