<?php

use App\Actions\GetReferenceReviewAgenda;
use App\Jobs\GenerateReadingNoteQuizJob;
use App\Models\AccessToken;
use App\Models\Chapter;
use App\Models\ChapterQuizQuestion;
use App\Models\ReadingNote;
use App\Models\ReferenceMaterial;
use App\Models\ReviewLog;
use App\Support\ReviewSubject;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

const CH_TODAY = '2026-09-17';

beforeEach(function () {
    Carbon::setTestNow(CH_TODAY);
    CarbonImmutable::setTestNow(CH_TODAY);

    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    $this->book = ReferenceMaterial::factory()->book()->create([
        'access_token_id' => $this->token->id,
        'title' => 'As Institutas',
    ]);

    Livewire::withoutLazyLoading();
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function bookAgenda(int $materialId): App\DTO\ReferenceReviewAgendaDTO
{
    return app(GetReferenceReviewAgenda::class)->handle($materialId, test()->token->id)->data;
}

test('a aba de anotações de um livro mostra o cartão de novo capítulo e o aviso de capítulo vazio', function () {
    Chapter::factory()->create(['reference_material_id' => $this->book->id, 'title' => 'Livro I']);

    Livewire::test('pages::referencia', ['id' => $this->book->id])
        ->set('activeTab', 'anotacoes')
        ->assertSee('Novo capítulo')
        ->assertSee('Livro I')
        ->assertSee('Sem anotação neste capítulo ainda');
});

test('cadastrar um capítulo sem anotação não o coloca na fila de revisão', function () {
    Livewire::test('pages::referencia', ['id' => $this->book->id])
        ->set('chapterForm.title', 'Capítulo 1')
        ->call('addChapter');

    $chapter = Chapter::where('reference_material_id', $this->book->id)->sole();

    expect($chapter->body)->toBeNull()
        ->and($chapter->next_review_at)->toBeNull()
        ->and(bookAgenda($this->book->id)->totalNotes)->toBe(0);
});

test('escrever a primeira anotação agenda a revisão para amanhã e apagar o texto tira da fila', function () {
    $chapter = Chapter::factory()->create(['reference_material_id' => $this->book->id, 'title' => 'Cap. 1']);

    $component = Livewire::test('pages::referencia', ['id' => $this->book->id])
        ->call('editChapterNote', $chapter->id)
        ->set('editChapterNoteForm.body', 'A fé vem pelo ouvir.')
        ->call('updateChapterNote');

    $chapter->refresh();
    expect($chapter->review_stage)->toBe(1)
        ->and($chapter->next_review_at->toDateString())->toBe('2026-09-18');

    $component
        ->call('editChapterNote', $chapter->id)
        ->set('editChapterNoteForm.body', '')
        ->call('updateChapterNote');

    $chapter->refresh();
    expect($chapter->body)->toBeNull()
        ->and($chapter->next_review_at)->toBeNull()
        ->and($chapter->review_stage)->toBe(0);
});

test('a anotação do capítulo precisa de pelo menos 3 caracteres quando preenchida', function () {
    $chapter = Chapter::factory()->create(['reference_material_id' => $this->book->id]);

    Livewire::test('pages::referencia', ['id' => $this->book->id])
        ->call('editChapterNote', $chapter->id)
        ->set('editChapterNoteForm.body', 'ab')
        ->call('updateChapterNote')
        ->assertHasErrors('editChapterNoteForm.body');
});

test('a fila de um livro cobra capítulos com anotação e ignora capítulo vazio', function () {
    $withNote = Chapter::factory()->dueOn(CH_TODAY)->create(['reference_material_id' => $this->book->id, 'title' => 'Com texto']);
    Chapter::factory()->create([
        'reference_material_id' => $this->book->id,
        'title' => 'Vazio',
        'review_stage' => 1,
        'next_review_at' => CH_TODAY,
    ]);

    $due = bookAgenda($this->book->id)->due;

    expect($due)->toHaveCount(1)
        ->and($due->first()->id)->toBe($withNote->id)
        ->and($due->first()->kind)->toBe(ReviewSubject::CHAPTER);
});

test('a anotação antiga de um livro não aparece na fila (o capítulo a substituiu)', function () {
    ReadingNote::factory()->dueOn(CH_TODAY)->create([
        'reference_material_id' => $this->book->id,
        'access_token_id' => $this->token->id,
    ]);

    expect(bookAgenda($this->book->id)->due)->toHaveCount(0);
});

test('a fila global mistura capítulos de livros e anotações de artigos', function () {
    $article = ReferenceMaterial::factory()->article()->create(['access_token_id' => $this->token->id]);
    ReadingNote::factory()->dueOn('2026-09-10')->create(['reference_material_id' => $article->id, 'access_token_id' => $this->token->id]);
    Chapter::factory()->dueOn(CH_TODAY)->create(['reference_material_id' => $this->book->id]);

    $due = app(GetReferenceReviewAgenda::class)->handle(null, $this->token->id)->data->due;

    expect($due->pluck('kind')->all())->toBe([ReviewSubject::NOTE, ReviewSubject::CHAPTER]);
});

test('acertar a MCQ de um capítulo sobe um degrau e grava o log apontando para o capítulo', function () {
    $chapter = Chapter::factory()->dueOn(CH_TODAY)->create(['reference_material_id' => $this->book->id]);

    $component = Livewire::test('revisoes-da-referencia', ['referenceMaterialId' => $this->book->id])
        ->call('openReview', $chapter->id, ReviewSubject::CHAPTER)
        ->call('pollCheckQuiz');

    $answers = collect($component->instance()->quizQuestions)
        ->mapWithKeys(fn (array $q): array => [$q['id'] => ChapterQuizQuestion::find($q['id'])->correct_answer])
        ->all();

    $component->set('quizAnswers', $answers)->call('submitQuiz')->assertSet('lastRecalled', true);

    $chapter->refresh();
    expect($chapter->review_stage)->toBe(2)
        ->and($chapter->next_review_at->toDateString())->toBe('2026-09-19')
        ->and(ReviewLog::where('reviewable_id', $chapter->id)->sole()->reviewable_type)->toBe(Chapter::class);
});

test('capítulo sem anotação não gera MCQ nem chama a IA', function () {
    $chapter = Chapter::factory()->create(['reference_material_id' => $this->book->id, 'title' => 'Só título']);

    (new GenerateReadingNoteQuizJob($chapter, ReviewSubject::CHAPTER))->handle(
        app(App\Actions\ValidateGeneratedQuestions::class),
        app(App\Actions\PersistReadingNoteQuizQuestions::class),
    );

    expect(ChapterQuizQuestion::count())->toBe(0)
        ->and(ReviewSubject::contentFor($chapter))->toBe('');
});

test('a migração copia as anotações de livros para capítulos, mantém o degrau, o vencimento e o histórico, e não duplica', function () {
    $note = ReadingNote::factory()->dueOn('2026-09-20', 3)->create([
        'reference_material_id' => $this->book->id,
        'access_token_id' => $this->token->id,
        'title' => 'Predestinação',
        'body' => 'Texto da anotação antiga.',
    ]);
    $article = ReferenceMaterial::factory()->article()->create(['access_token_id' => $this->token->id]);
    $articleNote = ReadingNote::factory()->create(['reference_material_id' => $article->id, 'access_token_id' => $this->token->id]);
    $log = ReviewLog::create([
        'reviewable_type' => ReadingNote::class,
        'reviewable_id' => $note->id,
        'access_token_id' => $this->token->id,
        'recalled' => true,
        'stage_before' => 2,
        'stage_after' => 3,
        'due_at' => '2026-09-16',
        'reviewed_at' => now(),
    ]);

    $run = fn () => (require database_path('migrations/2026_11_06_201040_move_book_reading_notes_into_chapters.php'))->up();
    $run();
    $run();

    $chapter = Chapter::where('legacy_reading_note_id', $note->id)->sole();

    expect($chapter->title)->toBe('Predestinação')
        ->and($chapter->body)->toBe('Texto da anotação antiga.')
        ->and($chapter->review_stage)->toBe(3)
        ->and($chapter->next_review_at->toDateString())->toBe('2026-09-20')
        ->and($log->refresh()->reviewable_type)->toBe(Chapter::class)
        ->and($log->reviewable_id)->toBe($chapter->id)
        ->and(Chapter::where('legacy_reading_note_id', $articleNote->id)->exists())->toBeFalse()
        ->and(ReadingNote::count())->toBe(2);
});
