<?php

use App\Actions\GetReviewAgenda;
use App\Actions\RecordNoteReview;
use App\DTO\DisciplinesDTO;
use App\Enums\Weekday;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\ReviewLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/** Quinta-feira. Toda a suíte roda como se hoje fosse dia de aula. */
const TODAY = '2026-09-17';

beforeEach(function () {
    Carbon::setTestNow(TODAY);
    CarbonImmutable::setTestNow(TODAY);

    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    Livewire::withoutLazyLoading();
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function disciplineOn(?Weekday $weekday, array $attributes = []): Disciplines
{
    return Disciplines::factory()->create([
        'title' => 'Teologia Sistemática',
        'class_weekday' => $weekday,
        ...$attributes,
    ]);
}

function dueNote(Disciplines $discipline, string $dueAt, int $stage = 1, array $attributes = []): Notes
{
    return Notes::factory()
        ->dueOn($dueAt, $stage)
        ->create([
            'discipline_id' => $discipline->id,
            'access_token_id' => test()->token->id,
            ...$attributes,
        ]);
}

function agenda(): App\DTO\ReviewAgendaDTO
{
    return app(GetReviewAgenda::class)
        ->handle(test()->token->id)
        ->data;
}

test('a fila do dia traz as notas devidas da disciplina que tem aula hoje', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    dueNote($discipline, TODAY, attributes: ['title' => 'A doutrina da Escritura']);

    expect(agenda()->due->pluck('title')->all())->toBe(['A doutrina da Escritura'])
        ->and(agenda()->totalDue)->toBe(1)
        ->and(agenda()->hasLessonToday)->toBeTrue();
});

test('notas de disciplina que não tem aula hoje ficam fora da fila', function () {
    dueNote(disciplineOn(Weekday::Monday), TODAY);

    expect(agenda()->due)->toBeEmpty()
        ->and(agenda()->hasLessonToday)->toBeFalse();
});

test('uma disciplina encerrada para de cobrar revisões', function () {
    dueNote(disciplineOn(Weekday::Thursday, ['completed_at' => now()]), TODAY);

    expect(agenda()->due)->toBeEmpty();
});

test('uma nota consolidada não volta para a fila', function () {
    $discipline = disciplineOn(Weekday::Thursday);

    Notes::factory()->consolidated()->create([
        'discipline_id' => $discipline->id,
        'access_token_id' => $this->token->id,
    ]);

    expect(agenda()->due)->toBeEmpty();
});

test('notas de outro token nunca aparecem na fila', function () {
    $discipline = disciplineOn(Weekday::Thursday);

    Notes::factory()->dueOn(TODAY)->create([
        'discipline_id' => $discipline->id,
        'access_token_id' => AccessToken::factory()->create()->id,
    ]);

    expect(agenda()->due)->toBeEmpty();
});

test('a fila respeita o teto e mostra as mais atrasadas primeiro', function () {
    $discipline = disciplineOn(Weekday::Thursday);

    dueNote($discipline, TODAY, attributes: ['title' => 'Desta aula']);
    dueNote($discipline, '2026-08-27', attributes: ['title' => 'Mais atrasada']);
    dueNote($discipline, '2026-09-10', attributes: ['title' => 'Atrasada uma aula']);

    $agenda = app(GetReviewAgenda::class)->handle($this->token->id, limit: 2)->data;

    expect($agenda->due->pluck('title')->all())->toBe(['Mais atrasada', 'Atrasada uma aula'])
        ->and($agenda->totalDue)->toBe(3)
        ->and($agenda->hiddenCount())->toBe(1);
});

test('a fila conta quantas aulas a nota está atrasada', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    dueNote($discipline, '2026-09-03');

    expect(agenda()->due->first()->lessons_overdue)->toBe(2)
        ->and(agenda()->due->first()->overdueLabel())->toBe('Atrasada 2 aulas');
});

test('lembrei sobe um degrau e agenda a cobrança duas aulas à frente', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    $note = dueNote($discipline, TODAY);

    $outcome = app(RecordNoteReview::class)->handle($note->id, $this->token->id, recalled: true);

    expect($outcome->success)->toBeTrue();

    $note->refresh();

    expect($note->review_stage)->toBe(2)
        ->and($note->next_review_at->toDateString())->toBe('2026-10-01')
        ->and($note->consolidated_at)->toBeNull();
});

test('travei devolve a nota ao primeiro degrau e cobra na próxima aula', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    $note = dueNote($discipline, TODAY, stage: 3);

    app(RecordNoteReview::class)->handle($note->id, $this->token->id, recalled: false);

    $note->refresh();

    expect($note->review_stage)->toBe(1)
        ->and($note->next_review_at->toDateString())->toBe('2026-09-24');
});

test('a quarta revisão bem sucedida consolida a nota e a tira da fila', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    $note = dueNote($discipline, TODAY, stage: 4);

    app(RecordNoteReview::class)->handle($note->id, $this->token->id, recalled: true);

    $note->refresh();

    expect($note->review_stage)->toBe(5)
        ->and($note->next_review_at)->toBeNull()
        ->and($note->consolidated_at)->not->toBeNull()
        ->and(agenda()->due)->toBeEmpty();
});

test('cada revisão deixa um registro no histórico', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    $note = dueNote($discipline, TODAY, stage: 2);

    app(RecordNoteReview::class)->handle($note->id, $this->token->id, recalled: true);

    $log = ReviewLog::first();

    expect($log->reviewable_type)->toBe($note->getMorphClass())
        ->and($log->reviewable_id)->toBe($note->id)
        ->and($log->recalled)->toBeTrue()
        ->and($log->stage_before)->toBe(2)
        ->and($log->stage_after)->toBe(3)
        ->and($log->due_at->toDateString())->toBe(TODAY)
        ->and($log->access_token_id)->toBe($this->token->id);
});

test('uma nota de outro token não pode ser revisada', function () {
    $discipline = disciplineOn(Weekday::Thursday);

    $note = Notes::factory()->dueOn(TODAY)->create([
        'discipline_id' => $discipline->id,
        'access_token_id' => AccessToken::factory()->create()->id,
    ]);

    $outcome = app(RecordNoteReview::class)->handle($note->id, $this->token->id, recalled: true);

    expect($outcome->success)->toBeFalse()
        ->and($note->refresh()->review_stage)->toBe(1);
});

test('definir o dia da aula traz as notas antigas da disciplina para a fila', function () {
    $discipline = disciplineOn(null);

    $note = Notes::factory()->create([
        'discipline_id' => $discipline->id,
        'access_token_id' => $this->token->id,
        'next_review_at' => null,
    ]);

    Livewire::test('pages::dashboard')
        ->call('openEditModal', $discipline->id)
        ->set('dto.class_weekday', Weekday::Thursday->value)
        ->call('saveDiscipline')
        ->assertHasNoErrors();

    expect($note->refresh()->next_review_at->toDateString())->toBe(TODAY)
        ->and($note->review_stage)->toBe(1)
        ->and(agenda()->due->pluck('id')->all())->toBe([$note->id]);
});

test('definir outro dia de aula agenda as notas antigas para o próximo encontro', function () {
    $discipline = disciplineOn(null);

    $note = Notes::factory()->create([
        'discipline_id' => $discipline->id,
        'access_token_id' => $this->token->id,
        'next_review_at' => null,
    ]);

    Livewire::test('pages::dashboard')
        ->call('openEditModal', $discipline->id)
        ->set('dto.class_weekday', Weekday::Friday->value)
        ->call('saveDiscipline')
        ->assertHasNoErrors();

    expect($note->refresh()->next_review_at->toDateString())->toBe('2026-09-18');
});

test('encerrar a disciplina pelo formulário tira ela da rotação', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    dueNote($discipline, TODAY);

    Livewire::test('pages::dashboard')
        ->call('openEditModal', $discipline->id)
        ->set('dto.is_completed', true)
        ->call('saveDiscipline')
        ->assertHasNoErrors();

    expect($discipline->refresh()->completed_at)->not->toBeNull()
        ->and(agenda()->due)->toBeEmpty();
});

test('uma nota nova nasce agendada para a próxima aula da disciplina', function () {
    $discipline = disciplineOn(Weekday::Thursday);

    $outcome = app(App\Actions\Orchestrators\SaveNote::class)->handle(new App\DTO\NotesDTO(
        discipline_id: $discipline->id,
        access_token_id: $this->token->id,
        title: 'A aula de hoje',
    ));

    expect($outcome->success)->toBeTrue();

    $note = Notes::where('title', 'A aula de hoje')->firstOrFail();

    expect($note->next_review_at->toDateString())->toBe('2026-09-24')
        ->and($note->review_stage)->toBe(1);
});

test('fora do dia de aula o painel mostra os próximos encontros', function () {
    $discipline = disciplineOn(Weekday::Monday, ['title' => 'Hermenêutica']);
    dueNote($discipline, TODAY);

    $agenda = agenda();

    $lesson = $agenda->upcoming->first();

    expect($agenda->hasLessonToday)->toBeFalse()
        ->and($lesson->disciplineTitle)->toBe('Hermenêutica')
        ->and($lesson->weekdayLabel)->toBe('Segunda-feira')
        ->and($lesson->date)->toBe('2026-09-21')
        ->and($lesson->dueCount)->toBe(1)
        ->and($lesson->dueLabel())->toBe('1 revisão');
});

test('o componente mostra os cartões da fila do dia', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    dueNote($discipline, TODAY, attributes: ['title' => 'A doutrina da Escritura']);

    Livewire::test('revisoes-do-dia')
        ->assertSee('Revisões de hoje')
        ->assertSee('A doutrina da Escritura')
        ->assertSee('Teologia Sistemática')
        ->assertSee('1ª revisão');
});

test('a modal abre cobrando o resumo em lacunas e sem entregar o corpo da nota', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    $note = dueNote($discipline, TODAY, attributes: [
        'summary' => 'A inspiração alcança as palavras do texto.',
        'impressions' => 'Impressão secreta da aula',
    ]);

    Livewire::test('revisoes-do-dia')
        ->call('openReview', $note->id)
        ->assertSet('showReviewModal', true)
        ->assertSet('clozeScore', null)
        ->assertSee('Complete o seu resumo')
        ->assertSee('Conferir')
        ->assertDontSee('Impressão secreta da aula')
        ->assertDontSee('Resultado');
});

test('responder o cloze puxa a próxima da fila sem fechar a modal', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    $first = dueNote($discipline, '2026-09-10', attributes: ['title' => 'Primeira da fila']);
    $second = dueNote($discipline, TODAY, attributes: ['title' => 'Segunda da fila']);

    Livewire::test('revisoes-do-dia')
        ->call('openReview', $first->id)
        ->call('giveUp')
        ->assertSet('clozeScore', 0)
        ->call('nextNote')
        ->assertSet('showReviewModal', true)
        ->assertSet('noteIdUnderReview', $second->id)
        ->assertSet('clozeScore', null);
});

test('a modal fecha quando a fila do dia acaba', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    $note = dueNote($discipline, TODAY);

    Livewire::test('revisoes-do-dia')
        ->call('openReview', $note->id)
        ->call('giveUp')
        ->call('nextNote')
        ->assertSet('showReviewModal', false)
        ->assertSet('noteIdUnderReview', null);
});

test('uma nota sem resumo escrito fica fora da fila do dia', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    Notes::factory()
        ->dueOn(TODAY)
        ->withoutSummary()
        ->create([
            'discipline_id' => $discipline->id,
            'access_token_id' => test()->token->id,
            'title' => 'Ainda não resumida',
        ]);

    expect(agenda()->due)->toHaveCount(0)
        ->and(agenda()->totalDue)->toBe(0);
});

test('a tela principal conta quantas notas ainda devem resumo', function () {
    $discipline = disciplineOn(Weekday::Thursday);
    dueNote($discipline, TODAY);

    Notes::factory()
        ->count(2)
        ->dueOn(TODAY)
        ->withoutSummary()
        ->create([
            'discipline_id' => $discipline->id,
            'access_token_id' => test()->token->id,
        ]);

    expect(agenda()->awaitingSummaryCount)->toBe(2)
        ->and(agenda()->awaitingSummaryLabel())->toBe('2 notas sem resumo');

    Livewire::test('revisoes-do-dia')
        ->assertSee('2 notas sem resumo')
        ->assertSee('Elas não entram na revisão enquanto você não escrever o resumo.');
});

test('uma nota consolidada não conta como pendente de resumo', function () {
    $discipline = disciplineOn(Weekday::Thursday);

    Notes::factory()
        ->consolidated()
        ->withoutSummary()
        ->create([
            'discipline_id' => $discipline->id,
            'access_token_id' => test()->token->id,
        ]);

    expect(agenda()->awaitingSummaryCount)->toBe(0);
});

test('uma nota que não está na fila de hoje não pode ser aberta', function () {
    $discipline = disciplineOn(Weekday::Monday);
    $note = dueNote($discipline, TODAY);

    Livewire::test('revisoes-do-dia')
        ->call('openReview', $note->id)
        ->assertSet('showReviewModal', false)
        ->assertSet('noteIdUnderReview', null);
});

test('criar uma disciplina pelo formulário guarda o dia da aula', function () {
    Livewire::test('pages::dashboard')
        ->call('openCreateModal')
        ->set('dto.title', 'Homilética')
        ->set('dto.class_weekday', Weekday::Tuesday->value)
        ->call('saveDiscipline')
        ->assertHasNoErrors();

    expect(Disciplines::where('title', 'Homilética')->firstOrFail()->class_weekday)
        ->toBe(Weekday::Tuesday);
});

test('um dia da semana inválido é rejeitado', function () {
    Livewire::test('pages::dashboard')
        ->call('openCreateModal')
        ->set('dto.title', 'Homilética')
        ->set('dto.class_weekday', 9)
        ->call('saveDiscipline')
        ->assertHasErrors(['dto.class_weekday']);

    $this->assertDatabaseMissing('disciplines', ['title' => 'Homilética']);
});

test('o DTO da disciplina carrega o dia da aula e o encerramento', function () {
    $discipline = disciplineOn(Weekday::Thursday, ['completed_at' => now()]);

    $dto = DisciplinesDTO::fromModel($discipline);

    expect($dto->class_weekday)->toBe(Weekday::Thursday->value)
        ->and($dto->is_completed)->toBeTrue();
});

test('o seeder de demonstração monta uma fila jogável para o token ativo', function () {
    $this->seed(Database\Seeders\SpacedReviewDemoSeeder::class);

    $agenda = agenda();

    expect($agenda->hasLessonToday)->toBeTrue()
        ->and($agenda->totalDue)->toBe(4)
        ->and($agenda->due->pluck('discipline_title')->unique()->all())->toBe(['Teologia Sistemática (demo)'])
        ->and(Disciplines::where('title', 'Hermenêutica (demo)')->firstOrFail()->completed_at)->not->toBeNull();
});

test('rodar o seeder duas vezes não duplica nada', function () {
    $this->seed(Database\Seeders\SpacedReviewDemoSeeder::class);
    $this->seed(Database\Seeders\SpacedReviewDemoSeeder::class);

    expect(Disciplines::count())->toBe(3)
        ->and(Notes::count())->toBe(9)
        ->and(agenda()->totalDue)->toBe(4);
});

test('a nota semeada sem resumo fica fora da fila e alimenta o aviso', function () {
    $this->seed(Database\Seeders\SpacedReviewDemoSeeder::class);

    $agenda = agenda();

    expect($agenda->due->pluck('title'))->not->toContain('A perseverança dos santos (demo)')
        ->and($agenda->awaitingSummaryCount)->toBe(1);
});

test('a fila semeada tem uma nota a um clique da consolidação', function () {
    $this->seed(Database\Seeders\SpacedReviewDemoSeeder::class);

    $note = Notes::where('title', 'A providência e o decreto divino (demo)')->firstOrFail();

    app(RecordNoteReview::class)->handle($note->id, $this->token->id, recalled: true);

    expect($note->refresh()->consolidated_at)->not->toBeNull()
        ->and(agenda()->totalDue)->toBe(3);
});
