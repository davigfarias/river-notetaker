<?php

use App\Actions\GenerateSummaryAudio;
use App\Enums\SummaryAudioStatus;
use App\Enums\Weekday;
use App\Jobs\GenerateSummaryAudioJob;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\NoteAudio;
use App\Models\Notes;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Audio;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Prompts\AudioPrompt;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    $this->discipline = Disciplines::factory()->create([
        'slug' => 'teologia-'.uniqid(),
        'class_weekday' => Weekday::fromDate(CarbonImmutable::now()),
    ]);
    $this->note = Notes::create([
        'title' => 'A graça de Deus',
        'discipline_id' => $this->discipline->id,
        'access_token_id' => $this->token->id,
        'summary' => 'A graça alcança o pecador antes de qualquer mérito dele.',
        'ai_summary' => 'A graça é o favor imerecido de Deus para com o pecador.',
        'review_stage' => 1,
        'next_review_at' => CarbonImmutable::now()->toDateString(),
    ]);
});

/**
 * O player vive na modal de revisão, e ela só entrega o resumo por IA depois
 * que o aluno responde o quiz de múltipla escolha do resumo escrito por ele.
 */
function reviewPlayerFor(Notes $note)
{
    return Livewire::test('revisoes-do-dia')
        ->call('openReview', $note->id)
        ->call('giveUp');
}

/** Grava um áudio em cache válido para o resumo atual da nota. */
function cacheAudioFor(Notes $note, string $bytes = 'audio-do-resumo'): NoteAudio
{
    return NoteAudio::updateOrCreate(
        ['note_id' => $note->id],
        [
            'status' => SummaryAudioStatus::Ready,
            'signature' => app(GenerateSummaryAudio::class)->signatureFor($note),
            'voice' => (string) config('tts.voice'),
            'mime' => 'audio/wav',
            'failure_reason' => null,
            'content' => base64_encode($bytes),
        ],
    );
}

test('the action queues the job when there is no audio yet', function () {
    Queue::fake();

    $outcome = app(GenerateSummaryAudio::class)->handle($this->note->id);

    expect($outcome->success)->toBeTrue();

    Queue::assertPushed(GenerateSummaryAudioJob::class, fn ($job) => $job->note->is($this->note));
});

test('the action does not spend quota when the cached audio still matches', function () {
    Queue::fake();

    cacheAudioFor($this->note);

    $outcome = app(GenerateSummaryAudio::class)->handle($this->note->id);

    expect($outcome->success)->toBeTrue();

    Queue::assertNothingPushed();
});

test('the action queues again once the summary changes', function () {
    Queue::fake();

    cacheAudioFor($this->note);

    $this->note->update(['ai_summary' => 'Outro resumo completamente diferente.']);

    app(GenerateSummaryAudio::class)->handle($this->note->id);

    Queue::assertPushed(GenerateSummaryAudioJob::class);
});

test('the action fails for a note without a summary', function () {
    Queue::fake();

    $this->note->update(['ai_summary' => null]);

    expect(app(GenerateSummaryAudio::class)->handle($this->note->id)->success)->toBeFalse();

    Queue::assertNothingPushed();
});

test('the action refuses a summary longer than the configured limit', function () {
    Queue::fake();

    config(['tts.max_characters' => 10]);

    $outcome = app(GenerateSummaryAudio::class)->handle($this->note->id);

    expect($outcome->success)->toBeFalse()
        ->and($outcome->message)->toContain('10');

    Queue::assertNothingPushed();
});

test('the job stores the generated audio against the current summary', function () {
    Audio::fake([base64_encode('audio-gerado')]);

    (new GenerateSummaryAudioJob($this->note->fresh()))->handle(app(GenerateSummaryAudio::class));

    $audio = $this->note->fresh()->audio;

    expect($audio)->not->toBeNull()
        ->and($audio->bytes())->toBe('audio-gerado')
        ->and($audio->signature)->toBe(app(GenerateSummaryAudio::class)->signatureFor($this->note->fresh()));

    Audio::assertGenerated(
        fn (AudioPrompt $prompt) => $prompt->contains('favor imerecido')
            && $prompt->voice === config('tts.voice'),
    );
});

test('the job replaces the previous audio instead of piling rows up', function () {
    cacheAudioFor($this->note, 'audio-velho');

    $this->note->update(['ai_summary' => 'Resumo novo da nota.']);

    Audio::fake([base64_encode('audio-novo')]);

    (new GenerateSummaryAudioJob($this->note->fresh()))->handle(app(GenerateSummaryAudio::class));

    expect(NoteAudio::where('note_id', $this->note->id)->count())->toBe(1)
        ->and($this->note->fresh()->audio->bytes())->toBe('audio-novo');
});

test('the route streams the cached audio', function () {
    cacheAudioFor($this->note);

    $response = $this->get(route('notas.resumo.audio', $this->note->id));

    $response->assertOk()->assertHeader('content-type', 'audio/wav');

    expect($response->getContent())->toBe('audio-do-resumo')
        ->and($response->headers->get('cache-control'))->toContain('private');
});

test('the route never generates audio on its own', function () {
    Audio::fake()->preventStrayAudio();

    $this->get(route('notas.resumo.audio', $this->note->id))->assertNotFound();

    Audio::assertNothingGenerated();
});

test('the route forbids a note belonging to another access token', function () {
    cacheAudioFor($this->note);

    $this->note->update(['access_token_id' => AccessToken::factory()->create()->id]);

    $this->get(route('notas.resumo.audio', $this->note->id))->assertForbidden();
});

test('the component shows the button before any audio exists', function () {
    reviewPlayerFor($this->note)
        ->assertSee('Ouvir com voz de IA');
});

test('the component renders the player once the audio is cached', function () {
    $audio = cacheAudioFor($this->note);

    reviewPlayerFor($this->note)
        ->assertDontSee('Ouvir com voz de IA')
        ->assertSee(substr($audio->signature, 0, 12))
        ->assertSee('x-data="aiAudioPlayer"', escape: false)
        // O player nativo foi trocado pelo customizado, então nada de `controls`.
        ->assertDontSee('<audio controls', escape: false);
});

test('the component polls only after the audio was requested', function () {
    Queue::fake();

    $component = reviewPlayerFor($this->note);

    expect($component->instance()->awaitingAudio)->toBeFalse();

    $component->call('generateSummaryAudio');

    expect($component->instance()->awaitingAudio)->toBeTrue();
});

test('polling stops itself once the audio lands', function () {
    Queue::fake();

    $component = reviewPlayerFor($this->note)
        ->call('generateSummaryAudio');

    cacheAudioFor($this->note);

    $component->call('pollCheckAudio');

    expect($component->instance()->awaitingAudio)->toBeFalse();
});

test('polling gives up after the deadline and blames the daily quota', function () {
    Queue::fake();

    $component = reviewPlayerFor($this->note)
        ->call('generateSummaryAudio');

    $this->travel(config('tts.job_timeout') + 60)->seconds();

    $component->call('pollCheckAudio');

    expect($component->instance()->awaitingAudio)->toBeFalse();
});

test('a rate limited job marks the record as failed with a readable reason', function () {
    cacheAudioFor($this->note);

    (new GenerateSummaryAudioJob($this->note->fresh()))
        ->failed(RateLimitedException::forProvider('gemini', 429));

    $audio = $this->note->fresh()->audio;

    expect($audio->status)->toBe(SummaryAudioStatus::Failed)
        ->and($audio->failure_reason)->toContain('cota diária')
        ->and($audio->content)->toBeNull();
});

test('an overloaded provider is reported as temporary, not as spent quota', function () {
    (new GenerateSummaryAudioJob($this->note))
        ->failed(ProviderOverloadedException::forProvider('gemini', 503));

    expect($this->note->fresh()->audio->failure_reason)->toContain('sobrecarregado');
});

test('the quota error is not retried, so it burns a single call', function () {
    Audio::fake(function () {
        throw RateLimitedException::forProvider('gemini', 429);
    });

    $job = Mockery::mock(GenerateSummaryAudioJob::class.'[fail]', [$this->note])
        ->shouldAllowMockingProtectedMethods();

    $job->shouldReceive('fail')->once()->with(Mockery::type(RateLimitedException::class));

    $job->handle(app(GenerateSummaryAudio::class));
});

test('the route refuses to serve a record that is still pending', function () {
    NoteAudio::factory()->pending()->create(['note_id' => $this->note->id]);

    $this->get(route('notas.resumo.audio', $this->note->id))->assertNotFound();
});

test('a failed record shows the button again instead of a broken player', function () {
    NoteAudio::factory()->failed()->create(['note_id' => $this->note->id]);

    reviewPlayerFor($this->note)
        ->assertSee('Ouvir com voz de IA');
});

test('polling surfaces the failure as soon as the job gives up', function () {
    Queue::fake();

    $component = reviewPlayerFor($this->note)
        ->call('generateSummaryAudio');

    expect($component->instance()->awaitingAudio)->toBeTrue();

    (new GenerateSummaryAudioJob($this->note->fresh()))
        ->failed(RateLimitedException::forProvider('gemini', 429));

    $component->call('pollCheckAudio');

    expect($component->instance()->awaitingAudio)->toBeFalse();
});

test('the summary header keeps only the regenerate button, so it survives phone widths', function () {
    $html = reviewPlayerFor($this->note)->html();

    // O cabeçalho é tudo entre o título do bloco e o texto do resumo.
    $header = str($html)->after('Resumo de IA')->before($this->note->ai_summary)->toString();

    // As bandeiras da Web Speech desceram para o bloco de áudio: no cabeçalho
    // elas empurravam o "Gerar novamente" para fora da tela no celular.
    expect($header)->not->toContain('🇧🇷')
        ->and($header)->not->toContain('🇺🇸')
        ->and($header)->toContain('Gerar novamente');

    // Continuam existindo, agora junto dos outros controles de áudio.
    expect($html)->toContain('🇧🇷')
        ->and($html)->toContain('🇺🇸')
        ->and($html)->toContain('Voz do navegador');
});
