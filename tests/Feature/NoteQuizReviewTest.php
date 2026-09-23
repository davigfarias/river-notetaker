<?php

use App\Ai\Agents\QuizQuestionGenerator;
use App\Enums\Weekday;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\QuizQuestion;
use App\Models\ReviewLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/** Quinta-feira. A suíte inteira roda como se hoje fosse dia de aula. */
const QUIZ_TODAY = '2026-09-17';

const QUIZ_SUMMARY = 'Inspiração verbal alcança palavras escritas pelos autores bíblicos.';

beforeEach(function () {
    Carbon::setTestNow(QUIZ_TODAY);
    CarbonImmutable::setTestNow(QUIZ_TODAY);

    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    $this->discipline = Disciplines::factory()->create([
        'title' => 'Teologia Sistemática',
        'class_weekday' => Weekday::Thursday,
    ]);

    $this->note = quizNote();
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function quizNote(array $attributes = []): Notes
{
    return Notes::factory()
        ->dueOn(QUIZ_TODAY)
        ->create([
            'discipline_id' => test()->discipline->id,
            'access_token_id' => test()->token->id,
            'title' => 'A inspiração das Escrituras',
            'summary' => QUIZ_SUMMARY,
            ...$attributes,
        ]);
}

/**
 * Cacheia um pool determinístico para a nota, do tamanho de
 * quiz.questions_per_session, para que a sessão sorteada seja sempre esse
 * conjunto inteiro — sem depender de qual subconjunto o sorteio escolheu.
 *
 * @param  array<int, array{question: string, correct_answer: string, distractors: array<int, string>}>  $questions
 */
function seedQuizPoolFor(Notes $note, array $questions): void
{
    config(['quiz.questions_per_session' => count($questions)]);

    foreach ($questions as $item) {
        QuizQuestion::factory()->create([
            'note_id' => $note->id,
            'question' => $item['question'],
            'correct_answer' => $item['correct_answer'],
            'distractors' => $item['distractors'],
            'content_hash' => hash('sha256', $note->summary),
        ]);
    }
}

test('a modal dispara a geração e, assim que o pool fica pronto, mostra as perguntas sem entregar o corpo da nota', function () {
    $this->note->update(['impressions' => 'Impressão secreta da aula']);

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    // Fila sync já rodou o job por trás; o próximo poll é quem pega o pool.
    expect($component->instance()->quizQuestions)->toBeEmpty()
        ->and($component->instance()->awaitingQuiz)->toBeTrue();

    $component->call('pollCheckQuiz');

    expect($component->instance()->quizQuestions)->not->toBeEmpty()
        ->and($component->instance()->awaitingQuiz)->toBeFalse();

    $component
        ->assertSee('Responda as perguntas')
        ->assertDontSee('Impressão secreta da aula')
        ->assertDontSee('Resultado');
});

test('com o pool já cacheado, sorteia as perguntas sem chamar a IA de novo', function () {
    seedQuizPoolFor($this->note, [
        ['question' => 'P1?', 'correct_answer' => 'R1', 'distractors' => ['D1a', 'D1b', 'D1c']],
        ['question' => 'P2?', 'correct_answer' => 'R2', 'distractors' => ['D2a', 'D2b', 'D2c']],
    ]);

    QuizQuestionGenerator::fake()->preventStrayPrompts();

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    expect($component->instance()->quizQuestions)->toHaveCount(2);

    QuizQuestionGenerator::assertNeverPrompted();
});

test('as perguntas expostas ao navegador não revelam qual alternativa é a correta', function () {
    seedQuizPoolFor($this->note, [
        ['question' => 'P1?', 'correct_answer' => 'R1', 'distractors' => ['D1a', 'D1b', 'D1c']],
    ]);

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    foreach ($component->instance()->quizQuestions as $question) {
        expect(array_keys($question))->toEqualCanonicalizing(['id', 'question', 'options']);
    }
});

test('acertar todas as perguntas sobe a nota um degrau e registra a revisão', function () {
    seedQuizPoolFor($this->note, [
        ['question' => 'P1?', 'correct_answer' => 'R1', 'distractors' => ['D1a', 'D1b', 'D1c']],
        ['question' => 'P2?', 'correct_answer' => 'R2', 'distractors' => ['D2a', 'D2b', 'D2c']],
    ]);

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    $answers = collect($component->instance()->quizQuestions)
        ->mapWithKeys(fn (array $q): array => [$q['id'] => QuizQuestion::find($q['id'])->correct_answer])
        ->all();

    $component
        ->set('quizAnswers', $answers)
        ->call('submitQuiz')
        ->assertSet('quizScore', 100)
        ->assertSet('lastRecalled', true);

    expect($this->note->refresh()->review_stage)->toBe(2)
        ->and($this->note->next_review_at->toDateString())->toBe('2026-10-01');

    $log = ReviewLog::where('reviewable_id', $this->note->id)->sole();

    expect($log->recalled)->toBeTrue()
        ->and($log->stage_before)->toBe(1)
        ->and($log->stage_after)->toBe(2);
});

test('errar as perguntas devolve a nota ao primeiro degrau', function () {
    seedQuizPoolFor($this->note, [
        ['question' => 'P1?', 'correct_answer' => 'R1', 'distractors' => ['D1a', 'D1b', 'D1c']],
        ['question' => 'P2?', 'correct_answer' => 'R2', 'distractors' => ['D2a', 'D2b', 'D2c']],
    ]);

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    $this->note->forceFill(['review_stage' => 3])->saveQuietly();

    $errado = collect($component->instance()->quizQuestions)
        ->mapWithKeys(fn (array $q): array => [$q['id'] => 'chute errado'])
        ->all();

    $component
        ->set('quizAnswers', $errado)
        ->call('submitQuiz')
        ->assertSet('quizScore', 0)
        ->assertSet('lastRecalled', false);

    expect($this->note->refresh()->review_stage)->toBe(1);
});

test('o piso de acerto configurado é o que decide a revisão', function () {
    config(['quiz.pass_score' => 50]);

    seedQuizPoolFor($this->note, [
        ['question' => 'P1?', 'correct_answer' => 'R1', 'distractors' => ['D1a', 'D1b', 'D1c']],
        ['question' => 'P2?', 'correct_answer' => 'R2', 'distractors' => ['D2a', 'D2b', 'D2c']],
        ['question' => 'P3?', 'correct_answer' => 'R3', 'distractors' => ['D3a', 'D3b', 'D3c']],
        ['question' => 'P4?', 'correct_answer' => 'R4', 'distractors' => ['D4a', 'D4b', 'D4c']],
        ['question' => 'P5?', 'correct_answer' => 'R5', 'distractors' => ['D5a', 'D5b', 'D5c']],
    ]);

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    $questions = collect($component->instance()->quizQuestions);

    expect($questions)->toHaveCount(5);

    // Três de cinco: 60%, acima do piso de 50 e abaixo do padrão de 70.
    $answers = $questions->mapWithKeys(fn (array $q): array => [$q['id'] => QuizQuestion::find($q['id'])->correct_answer])->all();
    $wrongIds = $questions->pluck('id')->take(2);

    foreach ($wrongIds as $id) {
        $answers[$id] = 'chute errado';
    }

    $component
        ->set('quizAnswers', $answers)
        ->call('submitQuiz')
        ->assertSet('quizScore', 60)
        ->assertSet('lastRecalled', true);
});

test('não lembro pontua zero sem precisar escolher nada', function () {
    seedQuizPoolFor($this->note, [
        ['question' => 'P1?', 'correct_answer' => 'R1', 'distractors' => ['D1a', 'D1b', 'D1c']],
    ]);

    Livewire::test('revisoes-do-dia')
        ->call('openReview', $this->note->id)
        ->call('giveUp')
        ->assertSet('quizScore', 0)
        ->assertSet('lastRecalled', false);

    expect(ReviewLog::where('reviewable_id', $this->note->id)->sole()->recalled)->toBeFalse();
});

test('não lembro registra a desistência mesmo enquanto as perguntas ainda estão sendo geradas', function () {
    Queue::fake();

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    expect($component->instance()->quizQuestions)->toBeEmpty();

    $component
        ->call('giveUp')
        ->assertSet('quizScore', 0)
        ->assertSet('lastRecalled', false);

    expect(ReviewLog::where('reviewable_id', $this->note->id)->sole()->recalled)->toBeFalse();
});

test('o resultado mostra a alternativa escolhida ao lado da correta quando erra', function () {
    seedQuizPoolFor($this->note, [
        ['question' => 'Qual é o sentido de X?', 'correct_answer' => 'Resposta certa', 'distractors' => ['Chute do aluno', 'D1b', 'D1c']],
    ]);

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    $questionId = $component->instance()->quizQuestions[0]['id'];

    $component
        ->set('quizAnswers', [$questionId => 'Chute do aluno'])
        ->call('submitQuiz');

    $result = collect($component->instance()->quizResults)->sole();

    expect($result['correct'])->toBeFalse()
        ->and($result['chosen'])->toBe('Chute do aluno')
        ->and($result['correct_answer'])->toBe('Resposta certa');

    $component->assertSee('Resultado')->assertSee('Chute do aluno')->assertSee('Resposta certa');
});

test('o resumo por IA só aparece depois de responder', function () {
    $this->note->update(['ai_summary' => 'O TL;DR gerado pela máquina.']);

    Livewire::test('revisoes-do-dia')
        ->call('openReview', $this->note->id)
        ->assertDontSee('O TL;DR gerado pela máquina.')
        ->call('giveUp')
        ->assertSee('O TL;DR gerado pela máquina.');
});

test('uma nota sem resumo escrito não entra na fila de revisão', function () {
    $sem = Notes::factory()
        ->dueOn(QUIZ_TODAY)
        ->withoutSummary()
        ->create([
            'discipline_id' => $this->discipline->id,
            'access_token_id' => $this->token->id,
        ]);

    Livewire::test('revisoes-do-dia')
        ->call('openReview', $sem->id)
        ->assertSet('showReviewModal', false);
});
