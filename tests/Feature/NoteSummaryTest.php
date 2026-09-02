<?php

use App\Actions\GenerateNoteSummary;
use App\Ai\Agents\Summarizer;
use App\Jobs\GenerateNoteSummaryJob;
use App\Models\AccessToken;
use App\Models\Concepts;
use App\Models\Disciplines;
use App\Models\Notes;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    $this->discipline = Disciplines::factory()->create(['slug' => 'teologia-'.uniqid()]);
    $this->note = Notes::create([
        'title' => 'A graça de Deus',
        'discipline_id' => $this->discipline->id,
        'access_token_id' => $this->token->id,
        'impressions' => 'Fui tocado pela profundidade do texto.',
        'life_experiences' => 'Lembrei de um período difícil da minha vida.',
    ]);
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

    Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->call('generateSummary')
        ->assertHasNoErrors();

    Queue::assertPushed(GenerateNoteSummaryJob::class, fn ($job) => $job->note->is($this->note));
});

test('the component starts polling only after a summary is requested', function () {
    Queue::fake();

    $component = Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug]);

    expect($component->instance()->awaitingSummary)->toBeFalse();

    $component->call('generateSummary');

    expect($component->instance()->awaitingSummary)->toBeTrue()
        ->and($component->get('awaitingSummaryNoteId'))->toBe($this->note->id);
});

test('polling stops itself once the deadline passes without a summary', function () {
    Queue::fake();

    $component = Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
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

    $component = Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->call('generateSummary');

    // Job has not run yet: old summary is still there, but the skeleton keeps polling.
    $component->call('pollCheckSummary');
    expect($component->instance()->awaitingSummary)->toBeTrue();

    (new GenerateNoteSummaryJob($this->note->fresh()))->handle();

    $component->call('pollCheckSummary');
    expect($component->instance()->awaitingSummary)->toBeFalse()
        ->and($component->instance()->selectedNote->ai_summary)->toBe('Resumo novo.');
})->group('regen');

test('pollCheckSummary exposes the summary through the selected note once ready', function () {
    Summarizer::fake(['Resumo pronto.']);

    $component = Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug]);

    expect($component->instance()->selectedNote->ai_summary)->toBeNull();

    (new GenerateNoteSummaryJob($this->note->fresh()))->handle();

    $component->call('pollCheckSummary');

    expect($component->instance()->selectedNote->ai_summary)->toBe('Resumo pronto.');
});
