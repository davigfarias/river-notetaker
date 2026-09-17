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
    $this->chapter = Chapter::factory()->create(['reference_material_id' => $this->material->id]);
});

test('submitting a cloze answer advances to the next question', function () {
    $cloze = Question::factory()->cloze()->create([
        'chapter_id' => $this->chapter->id,
        'position' => 0,
    ]);

    Question::factory()->create([
        'chapter_id' => $this->chapter->id,
        'position' => 1,
    ]);

    Livewire::test('pages::estudar', ['id' => $this->material->id, 'chapterId' => $this->chapter->id])
        ->assertSet('totalQuestions', 2)
        ->assertSet('index', 0)
        ->set('clozeInputs.3', 'aliança')
        ->set('clozeInputs.6', 'Abraão')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('index', 1);

    $this->assertDatabaseHas('question_attempts', [
        'question_id' => $cloze->id,
        'access_token_id' => $this->token->id,
        'score' => 100,
    ]);
});

test('advancing from a plain question into a cloze question renders the blanks', function () {
    Question::factory()->create(['chapter_id' => $this->chapter->id, 'position' => 0, 'reference_answer' => 'certo']);
    Question::factory()->cloze()->create(['chapter_id' => $this->chapter->id, 'position' => 1]);

    Livewire::test('pages::estudar', ['id' => $this->material->id, 'chapterId' => $this->chapter->id])
        ->set('answer', 'certo')
        ->call('submit')
        ->assertSet('index', 1)
        ->assertSet('isClozeQuestion', true)
        ->assertSee('wire:model="clozeInputs.3"', escape: false);
});

// Sem a key por pergunta o morph tenta converter o bloco cloze (inputs das
// lacunas, sem aside) no bloco de resposta livre (textarea + aside) em vez de
// substituí-lo, e a UI trava na pergunta anterior.
test('the question block is keyed per question so morph replaces it', function () {
    $cloze = Question::factory()->cloze()->create(['chapter_id' => $this->chapter->id, 'position' => 0]);
    $plain = Question::factory()->create(['chapter_id' => $this->chapter->id, 'position' => 1, 'reference_answer' => 'certo']);

    Livewire::test('pages::estudar', ['id' => $this->material->id, 'chapterId' => $this->chapter->id])
        ->assertSee('wire:key="question-'.$cloze->id.'"', escape: false)
        ->call('skip')
        ->assertSee('wire:key="question-'.$plain->id.'"', escape: false);
});

test('two cloze questions in a row both advance', function () {
    Question::factory()->cloze()->create(['chapter_id' => $this->chapter->id, 'position' => 0]);
    Question::factory()->cloze()->create(['chapter_id' => $this->chapter->id, 'position' => 1]);

    Livewire::test('pages::estudar', ['id' => $this->material->id, 'chapterId' => $this->chapter->id])
        ->set('clozeInputs.3', 'aliança')
        ->set('clozeInputs.6', 'Abraão')
        ->call('submit')
        ->assertSet('index', 1)
        ->assertSet('clozeInputs', [])
        ->call('submit')
        ->assertRedirect(route('referencias.study.results', ['id' => $this->material->id, 'chapterId' => $this->chapter->id]));
});

test('skipping a cloze question advances', function () {
    Question::factory()->cloze()->create(['chapter_id' => $this->chapter->id, 'position' => 0]);
    Question::factory()->cloze()->create(['chapter_id' => $this->chapter->id, 'position' => 1]);

    Livewire::test('pages::estudar', ['id' => $this->material->id, 'chapterId' => $this->chapter->id])
        ->call('skip')
        ->assertSet('index', 1);
});
