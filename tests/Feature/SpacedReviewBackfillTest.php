<?php

use App\Enums\Weekday;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\ReviewLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/** Sexta-feira. */
const BACKFILL_TODAY = '2026-09-18';

beforeEach(function () {
    Carbon::setTestNow(BACKFILL_TODAY);
    CarbonImmutable::setTestNow(BACKFILL_TODAY);

    $this->token = AccessToken::factory()->create();
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function runBackfill(): void
{
    (require database_path('migrations/2026_09_18_174459_backfill_spaced_review_for_existing_notes.php'))->up();
}

function legacyNote(Disciplines $discipline, string $createdAt): Notes
{
    $note = Notes::factory()->create([
        'discipline_id' => $discipline->id,
        'access_token_id' => test()->token->id,
        'review_stage' => 0,
        'next_review_at' => null,
    ]);

    $note->forceFill(['created_at' => $createdAt])->saveQuietly();

    return $note;
}

test('deduz o dia de aula pelas notas e cobra as antigas na aula de hoje', function () {
    $discipline = Disciplines::factory()->create(['class_weekday' => null]);

    $first = legacyNote($discipline, '2026-09-04 20:00:00');
    $second = legacyNote($discipline, '2026-09-11 21:00:00');

    runBackfill();

    expect($discipline->refresh()->class_weekday)->toBe(Weekday::Friday)
        ->and($first->refresh()->next_review_at->toDateString())->toBe(BACKFILL_TODAY)
        ->and($first->review_stage)->toBe(1)
        ->and($second->refresh()->next_review_at->toDateString())->toBe(BACKFILL_TODAY);
});

test('fora do dia de aula as notas antigas vão para o próximo encontro', function () {
    $discipline = Disciplines::factory()->create(['class_weekday' => null]);

    $note = legacyNote($discipline, '2026-09-08 20:00:00');

    runBackfill();

    expect($discipline->refresh()->class_weekday)->toBe(Weekday::Tuesday)
        ->and($note->refresh()->next_review_at->toDateString())->toBe('2026-09-22');
});

test('o dia de aula já definido é mantido', function () {
    $discipline = Disciplines::factory()->create(['class_weekday' => Weekday::Monday]);

    $note = legacyNote($discipline, '2026-09-11 20:00:00');

    runBackfill();

    expect($discipline->refresh()->class_weekday)->toBe(Weekday::Monday)
        ->and($note->refresh()->next_review_at->toDateString())->toBe('2026-09-21');
});

test('notas já agendadas, consolidadas ou de disciplina encerrada ficam como estão', function () {
    $active = Disciplines::factory()->create(['title' => 'Hermenêutica', 'class_weekday' => Weekday::Friday]);
    $completed = Disciplines::factory()->create(['title' => 'Evangelização', 'class_weekday' => null, 'completed_at' => now()]);

    $scheduled = Notes::factory()->dueOn('2026-10-02', 3)->create(['discipline_id' => $active->id]);
    $consolidated = Notes::factory()->consolidated()->create(['discipline_id' => $active->id]);
    $archived = legacyNote($completed, '2026-09-11 20:00:00');

    runBackfill();

    expect($scheduled->refresh()->next_review_at->toDateString())->toBe('2026-10-02')
        ->and($scheduled->review_stage)->toBe(3)
        ->and($consolidated->refresh()->next_review_at)->toBeNull()
        ->and($archived->refresh()->next_review_at)->toBeNull()
        ->and($completed->refresh()->class_weekday)->toBeNull();
});

test('notas antigas que o formulário empurrou para o próximo encontro voltam para hoje', function () {
    $discipline = Disciplines::factory()->create(['class_weekday' => Weekday::Friday]);

    $pushed = legacyNote($discipline, '2026-09-11 20:00:00');
    $pushed->forceFill(['review_stage' => 1, 'next_review_at' => '2026-09-25'])->saveQuietly();

    runBackfill();

    expect($pushed->refresh()->next_review_at->toDateString())->toBe(BACKFILL_TODAY);
});

test('notas escritas hoje ou já revisadas mantêm a data', function () {
    $discipline = Disciplines::factory()->create(['class_weekday' => Weekday::Friday]);

    $writtenToday = Notes::factory()->dueOn('2026-09-25')->create(['discipline_id' => $discipline->id]);

    $reviewed = legacyNote($discipline, '2026-09-04 20:00:00');
    $reviewed->forceFill(['review_stage' => 2, 'next_review_at' => '2026-10-02'])->saveQuietly();
    ReviewLog::query()->create([
        'reviewable_type' => Notes::class,
        'reviewable_id' => $reviewed->id,
        'access_token_id' => $this->token->id,
        'recalled' => true,
        'stage_before' => 1,
        'stage_after' => 2,
        'due_at' => '2026-09-11',
        'reviewed_at' => '2026-09-11 20:00:00',
    ]);

    runBackfill();

    expect($writtenToday->refresh()->next_review_at->toDateString())->toBe('2026-09-25')
        ->and($reviewed->refresh()->next_review_at->toDateString())->toBe('2026-10-02')
        ->and($reviewed->review_stage)->toBe(2);
});

test('disciplina sem notas continua sem dia de aula', function () {
    $discipline = Disciplines::factory()->create(['class_weekday' => null]);

    runBackfill();

    expect($discipline->refresh()->class_weekday)->toBeNull();
});
