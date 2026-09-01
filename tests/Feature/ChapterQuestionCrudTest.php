<?php

use App\Models\AccessToken;
use App\Models\Chapter;
use App\Models\Question;
use App\Models\ReferenceMaterial;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    $this->material = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id]);
});

test('a chapter can be created from the reference detail page', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('chapterForm.title', 'Capítulo 1')
        ->call('addChapter')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('chapters', [
        'reference_material_id' => $this->material->id,
        'title' => 'Capítulo 1',
        'position' => 0,
    ]);
});

test('chapter title is required', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('chapterForm.title', '')
        ->call('addChapter')
        ->assertHasErrors('chapterForm.title');
});

test('opening the create-chapter modal clears a stale validation error', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('chapterForm.title', '')
        ->call('addChapter')
        ->assertHasErrors('chapterForm.title')
        ->call('openCreateChapter')
        ->assertHasNoErrors()
        ->assertSet('creatingChapter', true);
});

test('a chapter can be edited and deleted', function () {
    $chapter = Chapter::factory()->create(['reference_material_id' => $this->material->id, 'title' => 'Antigo']);

    $component = Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('editChapter', $chapter->id)
        ->set('editChapterForm.title', 'Novo')
        ->call('updateChapter')
        ->assertHasNoErrors();

    expect($chapter->refresh()->title)->toBe('Novo');

    $component->call('confirmDeleteChapter', $chapter->id)->call('deleteChapter');

    $this->assertDatabaseMissing('chapters', ['id' => $chapter->id]);
});

test('a question can be created, edited, reordered and deleted', function () {
    $chapter = Chapter::factory()->create(['reference_material_id' => $this->material->id]);

    $component = Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('openCreateQuestion', $chapter->id)
        ->set('questionForm.prompt', 'Qual a capital?')
        ->set('questionForm.referenceAnswer', 'Brasília')
        ->call('addQuestion')
        ->assertHasNoErrors();

    $question = Question::where('chapter_id', $chapter->id)->firstOrFail();
    expect($question->prompt)->toBe('Qual a capital?');

    $second = Question::factory()->create(['chapter_id' => $chapter->id, 'position' => 1]);

    $component->call('moveQuestion', $second->id, 0);
    expect($second->refresh()->position)->toBe(0);
    expect($question->refresh()->position)->toBe(1);

    $component->call('editQuestion', $question->id)
        ->set('editQuestionForm.prompt', 'Editada?')
        ->call('updateQuestion')
        ->assertHasNoErrors();
    expect($question->refresh()->prompt)->toBe('Editada?');

    $component->call('confirmDeleteQuestion', $question->id)->call('deleteQuestion');
    $this->assertDatabaseMissing('questions', ['id' => $question->id]);
});

test('enabling cloze populates blank indices', function () {
    $chapter = Chapter::factory()->create(['reference_material_id' => $this->material->id]);

    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('openCreateQuestion', $chapter->id)
        ->set('questionForm.prompt', 'Complete')
        ->set('questionForm.referenceAnswer', 'Deus fez uma aliança eterna com Abraão')
        ->set('questionForm.isCloze', true)
        ->call('addQuestion')
        ->assertHasNoErrors();

    $question = Question::where('chapter_id', $chapter->id)->firstOrFail();
    expect($question->is_cloze)->toBeTrue();
    expect($question->cloze_blank_indices)->toBeArray()->not->toBeEmpty();
});

test('a token cannot manage chapters on a material it does not own', function () {
    $foreign = ReferenceMaterial::factory()->create(['access_token_id' => AccessToken::factory()->create()->id]);
    $chapter = Chapter::factory()->create(['reference_material_id' => $foreign->id, 'title' => 'Alheio']);

    Livewire::test('pages::referencia', ['id' => $foreign->id])->assertStatus(404);

    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('editChapter', $chapter->id)
        ->call('updateChapter');

    expect($chapter->refresh()->title)->toBe('Alheio');
});
