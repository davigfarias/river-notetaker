<?php

use App\Actions\GenerateNoteSummary;
use App\Ai\Agents\Summarizer;
use App\Enums\Weekday;
use App\Jobs\GenerateNoteSummaryJob;
use App\Models\AccessToken;
use App\Models\Concepts;
use App\Models\Disciplines;
use App\Models\Notes;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    $this->discipline = Disciplines::factory()->create([
        'slug' => 'teologia-'.uniqid(),
        'class_weekday' => Weekday::fromDate(CarbonImmutable::now()),
    ]);
    $this->note = dueNoteWithSummary($this->discipline->id, $this->token->id, 'A graça de Deus');
});

/**
 * Nota na fila de revisão de hoje. O resumo por IA agora só aparece dentro da
 * modal de revisão, depois que o aluno já respondeu o quiz do resumo dele.
 */
function dueNoteWithSummary(int $disciplineId, int $tokenId, string $title, array $attributes = []): Notes
{
    return Notes::create([
        'title' => $title,
        'discipline_id' => $disciplineId,
        'access_token_id' => $tokenId,
        'summary' => 'A graça é favor imerecido e alcança o pecador antes de qualquer mérito.',
        'impressions' => 'Fui tocado pela profundidade do texto.',
        'life_experiences' => 'Lembrei de um período difícil da minha vida.',
        'review_stage' => 1,
        'next_review_at' => CarbonImmutable::now()->toDateString(),
        ...$attributes,
    ]);
}

/**
 * Abre a revisão da nota e desiste do quiz, que é o que destrava a fase de
 * resultado — onde o resumo por IA vive.
 */
function reviewResultFor(Notes $note)
{
    return Livewire::test('revisoes-do-dia')
        ->call('openReview', $note->id)
        ->call('giveUp');
}

test('passing to the next note in the queue shows that note\'s own summary', function () {
    Queue::fake();

    $this->note->update(['ai_summary' => 'Resumo da nota A.']);

    $noteB = dueNoteWithSummary($this->discipline->id, $this->token->id, 'Segunda nota', [
        'ai_summary' => 'Resumo da nota B.',
    ]);

    reviewResultFor($this->note)
        ->assertSee('Resumo da nota A.')
        ->call('nextNote')
        ->assertSet('noteIdUnderReview', $noteB->id)
        ->call('giveUp')
        ->assertSee('Resumo da nota B.')
        ->assertDontSee('Resumo da nota A.');
});

test('the action dispatches the summary job for the note', function () {
    Queue::fake();

    $outcome = app(GenerateNoteSummary::class)->handle($this->note->id);

    expect($outcome->success)->toBeTrue();

    Queue::assertPushed(GenerateNoteSummaryJob::class, fn ($job) => $job->note->is($this->note));
});

test('the action fails for a missing note', function () {
    Queue::fake();

    $outcome = app(GenerateNoteSummary::class)->handle(999999);

    expect($outcome->success)->toBeFalse();
    Queue::assertNothingPushed();
});

test('the job stores the generated summary on the note', function () {
    Summarizer::fake(['Um resumo conciso do conteúdo da nota.']);

    Concepts::create([
        'note_id' => $this->note->id,
        'term' => 'Graça',
        'definition' => 'Favor imerecido de Deus.',
    ]);

    (new GenerateNoteSummaryJob($this->note->fresh()))->handle();

    expect($this->note->fresh()->ai_summary)->toBe('Um resumo conciso do conteúdo da nota.');
});

test('generating a summary from the component dispatches the job for the selected note', function () {
    Queue::fake();

    reviewResultFor($this->note)
        ->call('generateSummary')
        ->assertHasNoErrors();

    Queue::assertPushed(GenerateNoteSummaryJob::class, fn ($job) => $job->note->is($this->note));
});

test('the component starts polling only after a summary is requested', function () {
    Queue::fake();

    $component = reviewResultFor($this->note);

    expect($component->instance()->awaitingSummary)->toBeFalse();

    $component->call('generateSummary');

    expect($component->instance()->awaitingSummary)->toBeTrue()
        ->and($component->get('awaitingSummaryNoteId'))->toBe($this->note->id);
});

test('polling stops itself once the deadline passes without a summary', function () {
    Queue::fake();

    $component = reviewResultFor($this->note)
        ->call('generateSummary');

    expect($component->instance()->awaitingSummary)->toBeTrue();

    $this->travel(config('summarizer.job_timeout') + 30)->seconds();

    $component->call('pollCheckSummary');

    expect($component->instance()->awaitingSummary)->toBeFalse()
        ->and($component->get('awaitingSummaryNoteId'))->toBeNull();
});

test('regenerating waits for a summary different from the previous one', function () {
    Queue::fake();
    $this->note->update(['ai_summary' => 'Resumo antigo.']);
    Summarizer::fake(['Resumo novo.']);

    $component = reviewResultFor($this->note)
        ->call('generateSummary');

    // Job has not run yet: old summary is still there, but the skeleton keeps polling.
    $component->call('pollCheckSummary');
    expect($component->instance()->awaitingSummary)->toBeTrue();

    (new GenerateNoteSummaryJob($this->note->fresh()))->handle();

    $component->call('pollCheckSummary');
    expect($component->instance()->awaitingSummary)->toBeFalse()
        ->and($component->instance()->noteUnderReview->ai_summary)->toBe('Resumo novo.');
})->group('regen');

test('pollCheckSummary exposes the summary through the selected note once ready', function () {
    Summarizer::fake(['Resumo pronto.']);

    $component = reviewResultFor($this->note);

    expect($component->instance()->noteUnderReview->ai_summary)->toBeNull();

    (new GenerateNoteSummaryJob($this->note->fresh()))->handle();

    $component->call('pollCheckSummary');

    expect($component->instance()->noteUnderReview->ai_summary)->toBe('Resumo pronto.');
});

test('the job keeps a summary that is already within the tolerated limit', function () {
    config(['summarizer.max_characters' => 500]);

    Summarizer::fake([str_repeat('a', 400)]);

    (new GenerateNoteSummaryJob($this->note->fresh()))->handle();

    expect($this->note->fresh()->ai_summary)->toBe(str_repeat('a', 400));
});

test('the job asks the model to shorten a summary that blows the limit', function () {
    config(['summarizer.max_characters' => 500, 'summarizer.target_characters' => 300]);

    Summarizer::fake([str_repeat('a', 900), str_repeat('b', 280)]);

    (new GenerateNoteSummaryJob($this->note->fresh()))->handle();

    expect($this->note->fresh()->ai_summary)->toBe(str_repeat('b', 280));

    Summarizer::assertPrompted(fn ($prompt) => $prompt->contains("Encurte o texto"));
});

test('the job keeps the original when shortening comes back even longer', function () {
    config(['summarizer.max_characters' => 500, 'summarizer.target_characters' => 300]);

    Summarizer::fake([str_repeat('a', 900), str_repeat('b', 1200)]);

    (new GenerateNoteSummaryJob($this->note->fresh()))->handle();

    expect($this->note->fresh()->ai_summary)->toBe(str_repeat('a', 900));
});

test('the summarizer instructions carry the target length and the speech constraints', function () {
    config(['summarizer.target_characters' => 300]);

    $instructions = (string) (new Summarizer)->instructions();

    expect($instructions)->toContain('300')
        ->and($instructions)->toContain('OUVIDO em voz alta')
        ->and($instructions)->toContain('parênteses');
});
