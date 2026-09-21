<?php

use App\Actions\SelectClozeBlanks;
use App\Actions\TokenizeAnswerText;
use App\Enums\Weekday;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\ReviewLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/** Quinta-feira. A suíte inteira roda como se hoje fosse dia de aula. */
const CLOZE_TODAY = '2026-09-17';

/**
 * Resumo com palavras longas e distintas: nenhuma cai na lista de palavras
 * funcionais, então todas são elegíveis a virar lacuna.
 */
const CLOZE_SUMMARY = 'Inspiração verbal alcança palavras escritas pelos autores bíblicos.';

beforeEach(function () {
    Carbon::setTestNow(CLOZE_TODAY);
    CarbonImmutable::setTestNow(CLOZE_TODAY);

    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    $this->discipline = Disciplines::factory()->create([
        'title' => 'Teologia Sistemática',
        'class_weekday' => Weekday::Thursday,
    ]);

    $this->note = clozeNote();

    Livewire::withoutLazyLoading();
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function clozeNote(array $attributes = []): Notes
{
    return Notes::factory()
        ->dueOn(CLOZE_TODAY)
        ->create([
            'discipline_id' => test()->discipline->id,
            'access_token_id' => test()->token->id,
            'title' => 'A inspiração das Escrituras',
            'summary' => CLOZE_SUMMARY,
            ...$attributes,
        ]);
}

/**
 * As respostas certas para as lacunas que o componente sorteou.
 *
 * @return array<int, string>
 */
function correctAnswersFor(array $blankIndices, string $summary = CLOZE_SUMMARY): array
{
    $tokens = app(TokenizeAnswerText::class)->handle($summary)->data;

    $words = [];

    foreach ($tokens as $token) {
        if ($token['word']) {
            $words[$token['index']] = $token['text'];
        }
    }

    return collect($blankIndices)
        ->mapWithKeys(fn (int $index): array => [$index => $words[$index]])
        ->all();
}

test('a modal abre com o resumo em lacunas e sem entregar o corpo da nota', function () {
    $this->note->update(['impressions' => 'Impressão secreta da aula']);

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    expect($component->instance()->clozeIndices)->not->toBeEmpty();

    $component
        ->assertSee('Complete o seu resumo')
        ->assertSee('cloze-blank', escape: false)
        ->assertDontSee('Impressão secreta da aula')
        ->assertDontSee('Resultado');
});

test('acertar todas as lacunas sobe a nota um degrau e registra a revisão', function () {
    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    $component
        ->set('clozeInputs', correctAnswersFor($component->instance()->clozeIndices))
        ->call('submitCloze')
        ->assertSet('clozeScore', 100)
        ->assertSet('lastRecalled', true);

    expect($this->note->refresh()->review_stage)->toBe(2)
        ->and($this->note->next_review_at->toDateString())->toBe('2026-10-01');

    $log = ReviewLog::where('reviewable_id', $this->note->id)->sole();

    expect($log->recalled)->toBeTrue()
        ->and($log->stage_before)->toBe(1)
        ->and($log->stage_after)->toBe(2);
});

test('errar as lacunas devolve a nota ao primeiro degrau', function () {
    $component = Livewire::test('revisoes-do-dia')
        ->call('openReview', $this->note->id);

    $this->note->forceFill(['review_stage' => 3])->saveQuietly();

    $errado = collect($component->instance()->clozeIndices)
        ->mapWithKeys(fn (int $index): array => [$index => 'errado'])
        ->all();

    $component
        ->set('clozeInputs', $errado)
        ->call('submitCloze')
        ->assertSet('clozeScore', 0)
        ->assertSet('lastRecalled', false);

    expect($this->note->refresh()->review_stage)->toBe(1);
});

test('a correção ignora acentos e caixa alta', function () {
    $note = clozeNote(['summary' => 'Inspiração verbal alcança palavras bíblicas.']);

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $note->id);

    $respostas = collect(correctAnswersFor(
        $component->instance()->clozeIndices,
        'Inspiração verbal alcança palavras bíblicas.',
    ))->map(fn (string $palavra): string => mb_strtoupper(
        strtr($palavra, ['ã' => 'a', 'í' => 'i', 'ç' => 'c']),
    ))->all();

    $component
        ->set('clozeInputs', $respostas)
        ->call('submitCloze')
        ->assertSet('clozeScore', 100);
});

test('o piso de acerto configurado é o que decide a revisão', function () {
    config(['cloze.pass_score' => 50, 'cloze.blank_ratio' => 1.0]);

    $note = clozeNote(['summary' => 'Inspiração verbal alcança palavras bíblicas.']);

    $component = Livewire::test('revisoes-do-dia')->call('openReview', $note->id);

    $indices = $component->instance()->clozeIndices;

    expect($indices)->toHaveCount(5);

    // Três de cinco: 60%, acima do piso de 50 e abaixo do padrão de 70.
    $respostas = correctAnswersFor($indices, 'Inspiração verbal alcança palavras bíblicas.');
    $respostas[$indices[3]] = 'errado';
    $respostas[$indices[4]] = 'errado';

    $component
        ->set('clozeInputs', $respostas)
        ->call('submitCloze')
        ->assertSet('clozeScore', 60)
        ->assertSet('lastRecalled', true);
});

test('não lembro pontua zero sem precisar chutar as lacunas', function () {
    Livewire::test('revisoes-do-dia')
        ->call('openReview', $this->note->id)
        ->call('giveUp')
        ->assertSet('clozeScore', 0)
        ->assertSet('lastRecalled', false);

    expect(ReviewLog::where('reviewable_id', $this->note->id)->sole()->recalled)->toBeFalse();
});

test('o resultado mostra o que foi digitado ao lado do que era esperado', function () {
    $component = Livewire::test('revisoes-do-dia')->call('openReview', $this->note->id);

    $indices = $component->instance()->clozeIndices;
    $esperado = correctAnswersFor($indices);

    $respostas = $esperado;
    $respostas[$indices[0]] = 'chute';

    $component->set('clozeInputs', $respostas)->call('submitCloze');

    $blanks = collect($component->instance()->clozeBlanks)->keyBy('index');

    expect($blanks[$indices[0]]['correct'])->toBeFalse()
        ->and($blanks[$indices[0]]['given'])->toBe('chute')
        ->and($blanks[$indices[0]]['expected'])->toBe($esperado[$indices[0]]);

    $component->assertSee('Resultado')->assertSee('chute');
});

test('o resumo por IA só aparece depois de responder', function () {
    $this->note->update(['ai_summary' => 'O TL;DR gerado pela máquina.']);

    Livewire::test('revisoes-do-dia')
        ->call('openReview', $this->note->id)
        ->assertDontSee('O TL;DR gerado pela máquina.')
        ->call('giveUp')
        ->assertSee('O TL;DR gerado pela máquina.');
});

test('as lacunas são sorteadas de novo a cada abertura da nota', function () {
    config(['cloze.blank_ratio' => 0.5]);

    $note = clozeNote([
        'summary' => 'Inspiração verbal alcança palavras escritas pelos autores bíblicos antigos, segundo confissões reformadas.',
    ]);

    $sorteios = collect(range(1, 12))->map(
        fn (): array => Livewire::test('revisoes-do-dia')
            ->call('openReview', $note->id)
            ->instance()
            ->clozeIndices,
    );

    expect($sorteios->unique()->count())->toBeGreaterThan(1);
});

test('uma nota sem resumo não pode ser cobrada em lacunas', function () {
    $sem = Notes::factory()
        ->dueOn(CLOZE_TODAY)
        ->withoutSummary()
        ->create([
            'discipline_id' => $this->discipline->id,
            'access_token_id' => $this->token->id,
        ]);

    Livewire::test('revisoes-do-dia')
        ->call('openReview', $sem->id)
        ->assertSet('showReviewModal', false);
});

test('o sorteio escolhe palavras de conteúdo, nunca só palavras funcionais', function () {
    $tokens = app(TokenizeAnswerText::class)->handle(CLOZE_SUMMARY)->data;

    $indices = app(SelectClozeBlanks::class)->handle($tokens)->data;

    $palavras = collect($tokens)
        ->filter(fn (array $token): bool => $token['word'] && in_array($token['index'], $indices, true))
        ->pluck('text');

    expect($palavras)->not->toBeEmpty()
        ->and($palavras->every(fn (string $palavra): bool => mb_strlen($palavra) >= 2))->toBeTrue();
});
