<?php

use App\Actions\ComputeAnswerSimilarity;
use App\Actions\ComputeReferenceMaterialProgress;
use App\Actions\GradeClozeBlanks;
use App\Actions\SelectClozeBlanks;
use App\Actions\TokenizeAnswerText;
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

test('answer similarity scores identical text as 100', function () {
    $score = app(ComputeAnswerSimilarity::class)->handle(['deus', 'e', 'amor'], ['deus', 'e', 'amor'])->data;

    expect($score)->toBe(100);
});

test('cloze blank selection skips stopwords and short words', function () {
    $tokens = app(TokenizeAnswerText::class)->handle('Deus fez uma aliança eterna com Abraão')->data;
    $blanks = app(SelectClozeBlanks::class)->handle($tokens, ratio: 1.0)->data;

    // "uma" and "com" are stopwords; indices 0,1,3,4,6 remain eligible.
    expect($blanks)->not->toContain(2)->not->toContain(5);
});

test('cloze grading is accent insensitive', function () {
    $tokens = app(TokenizeAnswerText::class)->handle('Deus fez uma aliança eterna com Abraão')->data;
    $result = app(GradeClozeBlanks::class)->handle($tokens, [3], [3 => 'alianca'])->data;

    expect($result['score'])->toBe(100);
    expect($result['blanks'][0]['correct'])->toBeTrue();
});

test('study session flow: submit an answer then advance to results', function () {
    $question = Question::factory()->create([
        'chapter_id' => $this->chapter->id,
        'reference_answer' => 'A resposta certa',
    ]);

    Livewire::test('pages::estudar', ['id' => $this->material->id, 'chapterId' => $this->chapter->id])
        ->assertSet('totalQuestions', 1)
        ->set('answer', 'A resposta certa')
        ->call('submit')
        ->assertRedirect(route('referencias.study.results', ['id' => $this->material->id, 'chapterId' => $this->chapter->id]));

    $this->assertDatabaseHas('question_attempts', [
        'question_id' => $question->id,
        'access_token_id' => $this->token->id,
        'score' => 100,
    ]);
});

test('skipping records a skipped attempt', function () {
    $question = Question::factory()->create(['chapter_id' => $this->chapter->id]);

    Livewire::test('pages::estudar', ['id' => $this->material->id, 'chapterId' => $this->chapter->id])
        ->call('skip');

    $this->assertDatabaseHas('question_attempts', [
        'question_id' => $question->id,
        'skipped' => true,
    ]);
});

test('progress reflects attempted questions', function () {
    Question::factory()->count(2)->create(['chapter_id' => $this->chapter->id]);
    $answered = Question::factory()->create(['chapter_id' => $this->chapter->id]);
    $answered->attempts()->create(['access_token_id' => $this->token->id, 'answer_text' => 'x', 'score' => 50, 'skipped' => false]);

    $progress = app(ComputeReferenceMaterialProgress::class)->handle($this->material, $this->token)->data;

    expect($progress->totalQuestions)->toBe(3);
    expect($progress->attemptedQuestions)->toBe(1);
    expect($progress->percent)->toBe(33);
});

test('results page builds a row per attempted question', function () {
    $question = Question::factory()->create(['chapter_id' => $this->chapter->id, 'reference_answer' => 'certo']);
    $question->attempts()->create(['access_token_id' => $this->token->id, 'answer_text' => 'certo', 'score' => 100, 'skipped' => false]);

    Livewire::test('pages::resultados', ['id' => $this->material->id, 'chapterId' => $this->chapter->id])
        ->assertOk();
});

test('a foreign token cannot study a chapter', function () {
    Question::factory()->create(['chapter_id' => $this->chapter->id]);
    $this->withSession(['access_token_id' => AccessToken::factory()->create()->id]);

    Livewire::test('pages::estudar', ['id' => $this->material->id, 'chapterId' => $this->chapter->id])
        ->assertStatus(404);
});

test('review page renders questions for a chapter', function () {
    Question::factory()->count(2)->create(['chapter_id' => $this->chapter->id]);

    Livewire::test('pages::revisao', ['id' => $this->material->id, 'chapterId' => $this->chapter->id])
        ->assertOk()
        ->assertSet('totalQuestions', 2)
        ->call('next')
        ->assertSet('index', 1);
});
