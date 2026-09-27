<?php

use App\Models\AccessToken;
use App\Models\Chapter;
use App\Models\Citation;
use App\Models\Question;
use App\Models\ReadingNote;
use App\Models\ReferenceMaterial;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    $this->material = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();
});

test('a citation is edited in place with the markdown editor and closes on save', function () {
    $citation = Citation::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'quote_text' => 'Trecho original.',
    ]);

    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('editCitation', $citation->id)
        ->assertSet('editingCitationId', $citation->id)
        ->assertSeeHtml("markdownEditor('editCitationForm.quote_text')")
        ->set('editCitationForm.quote_text', 'Trecho editado.')
        ->call('updateCitation')
        ->assertHasNoErrors()
        ->assertSet('editingCitationId', null)
        ->assertDontSeeHtml("markdownEditor('editCitationForm.quote_text')");

    expect($citation->fresh()->quote_text)->toBe('Trecho editado.');
});

test('a reading note is edited in place with the markdown editor', function () {
    $note = ReadingNote::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
    ]);

    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('editReadingNote', $note->id)
        ->assertSeeHtml("markdownEditor('editReadingNoteForm.body')")
        ->set('editReadingNoteForm.body', 'Anotação editada.')
        ->call('updateReadingNote')
        ->assertHasNoErrors()
        ->assertSet('editingReadingNoteId', null);

    expect($note->fresh()->body)->toBe('Anotação editada.');
});

test('a chapter title is edited in place outside its details element', function () {
    $chapter = Chapter::factory()->create(['reference_material_id' => $this->material->id, 'title' => 'Antigo']);

    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('editChapter', $chapter->id)
        ->assertSeeHtml('wire:key="edit-chapter-'.$chapter->id.'"')
        ->assertDontSeeHtml('wire:key="chapter-'.$chapter->id.'"')
        ->set('editChapterForm.title', 'Novo')
        ->call('updateChapter')
        ->assertSet('editingChapterId', null)
        ->assertSeeHtml('wire:key="chapter-'.$chapter->id.'"');

    expect($chapter->fresh()->title)->toBe('Novo');
});

test('a question is edited in place and cancelling keeps it untouched', function () {
    $chapter = Chapter::factory()->create(['reference_material_id' => $this->material->id]);
    $question = Question::factory()->create(['chapter_id' => $chapter->id, 'prompt' => 'Original?']);

    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('editQuestion', $question->id)
        ->assertSeeHtml('wire:model="editQuestionForm.prompt"')
        ->set('editQuestionForm.prompt', 'Descartada?')
        ->set('editingQuestionId', null)
        ->assertDontSeeHtml('wire:model="editQuestionForm.prompt"');

    expect($question->fresh()->prompt)->toBe('Original?');
});

test('the material is edited in place in the page header', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('openEditMaterial')
        ->assertSeeHtml('wire:model="editForm.title"')
        ->set('editForm.title', 'Título novo')
        ->call('updateMaterial')
        ->assertHasNoErrors()
        ->assertSet('editingMaterial', false)
        ->assertDontSeeHtml('wire:model="editForm.title"');

    expect($this->material->fresh()->title)->toBe('Título novo');
});
