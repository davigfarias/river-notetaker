<?php

use App\Actions\DismissReadingNoteReview;
use App\Actions\GetReferenceReviewAgenda;
use App\Actions\RestoreReadingNoteReview;
use App\Models\AccessToken;
use App\Models\ReadingNote;
use App\Models\ReferenceMaterial;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-09-17');
    CarbonImmutable::setTestNow('2026-09-17');

    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    Livewire::withoutLazyLoading();
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function reviewNoteFor(AccessToken $token, string $dueAt, array $attributes = [], ?ReferenceMaterial $material = null, int $stage = 2): ReadingNote
{
    $material ??= ReferenceMaterial::factory()->create(['access_token_id' => $token->id]);

    return ReadingNote::factory()->dueOn($dueAt, $stage)->create([
        'reference_material_id' => $material->id,
        'access_token_id' => $token->id,
        ...$attributes,
    ]);
}

test('a fila global reúne as anotações vencidas de todas as referências do usuário', function () {
    $first = reviewNoteFor($this->token, '2026-09-17', ['title' => 'Da obra A']);
    $second = reviewNoteFor($this->token, '2026-09-15', ['title' => 'Da obra B']);

    $strangerToken = AccessToken::factory()->create();
    reviewNoteFor($strangerToken, '2026-09-10', ['title' => 'De outro usuário']);

    $agenda = app(GetReferenceReviewAgenda::class)->handle(null, $this->token->id)->data;

    expect($agenda->due->pluck('title')->all())->toBe(['Da obra B', 'Da obra A'])
        ->and($agenda->due->pluck('reference_title')->filter()->count())->toBe(2)
        ->and($agenda->totalDue)->toBe(2);
});

test('dispensar tira da fila sem mexer no degrau nem apagar a anotação', function () {
    $note = reviewNoteFor($this->token, '2026-09-17', stage: 3);

    $outcome = app(DismissReadingNoteReview::class)->handle($note->id, $this->token->id);

    expect($outcome->success)->toBeTrue();

    $note->refresh();

    expect($note->dismissed_at)->not->toBeNull()
        ->and($note->review_stage)->toBe(3)
        ->and($note->isDueForReview(CarbonImmutable::now()))->toBeFalse();

    $agenda = app(GetReferenceReviewAgenda::class)->handle(null, $this->token->id)->data;

    expect($agenda->isEmpty())->toBeTrue()
        ->and($agenda->dismissedCount)->toBe(1)
        ->and($agenda->totalNotes)->toBe(1);
});

test('não dá para dispensar nem devolver a anotação de outro usuário', function () {
    $strangerToken = AccessToken::factory()->create();
    $note = reviewNoteFor($strangerToken, '2026-09-17');

    expect(app(DismissReadingNoteReview::class)->handle($note->id, $this->token->id)->success)->toBeFalse()
        ->and(app(RestoreReadingNoteReview::class)->handle($note->id, $this->token->id)->success)->toBeFalse()
        ->and($note->refresh()->dismissed_at)->toBeNull();
});

test('devolver à fila faz a anotação cobrar hoje no mesmo degrau', function () {
    $note = reviewNoteFor($this->token, '2026-10-20', ['dismissed_at' => now()], stage: 3);

    $outcome = app(RestoreReadingNoteReview::class)->handle($note->id, $this->token->id);

    expect($outcome->success)->toBeTrue();

    $note->refresh();

    expect($note->dismissed_at)->toBeNull()
        ->and($note->review_stage)->toBe(3)
        ->and($note->next_review_at->toDateString())->toBe('2026-09-17');

    expect(app(GetReferenceReviewAgenda::class)->handle(null, $this->token->id)->data->totalDue)->toBe(1);
});

test('a fila de uma referência também ignora as dispensadas', function () {
    $material = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id]);
    reviewNoteFor($this->token, '2026-09-17', ['title' => 'Fica'], $material);
    reviewNoteFor($this->token, '2026-09-17', ['title' => 'Sai', 'dismissed_at' => now()], $material);

    $agenda = app(GetReferenceReviewAgenda::class)->handle($material->id, $this->token->id)->data;

    expect($agenda->due->pluck('title')->all())->toBe(['Fica']);
});

test('o painel principal mostra a fila de recursos e deixa tirar e devolver', function () {
    $note = reviewNoteFor($this->token, '2026-09-17', ['title' => 'Anotação para revisar']);

    Livewire::test('revisoes-da-referencia')
        ->assertSee('Revisões de recursos')
        ->assertSee('Anotação para revisar')
        ->call('confirmDismiss', $note->id)
        ->assertSet('showDismissConfirm', true)
        ->assertSee('Tirar da fila de revisão?')
        ->call('dismiss')
        ->assertDontSee('Anotação para revisar')
        ->assertSee('Tiradas da fila (1)')
        ->set('showDismissed', true)
        ->assertSee('Anotação para revisar')
        ->call('restore', $note->id)
        ->assertSee('Anotação para revisar')
        ->assertDontSee('Tiradas da fila (');
});

test('cancelar a confirmação mantém a anotação na fila', function () {
    $note = reviewNoteFor($this->token, '2026-09-17', ['title' => 'Continua aqui']);

    Livewire::test('revisoes-da-referencia')
        ->call('confirmDismiss', $note->id)
        ->call('cancelDismiss')
        ->assertSet('showDismissConfirm', false)
        ->call('dismiss')
        ->assertSee('Continua aqui');

    expect($note->refresh()->dismissed_at)->toBeNull();
});

test('o painel principal explica cada estado vazio', function () {
    Livewire::test('revisoes-da-referencia')
        ->assertSee('Você ainda não tem anotações de recursos.');

    reviewNoteFor($this->token, '2026-09-20', ['title' => 'Futura']);

    Livewire::test('revisoes-da-referencia')
        ->assertSee('Nenhuma revisão pendente para hoje.')
        ->assertSee('A próxima vence em 20/09.');
});

test('o dashboard exibe as duas filas lado a lado', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Revisões de hoje')
        ->assertSee('Revisões de recursos');
});

test('com muitas anotações vencidas só 8 aparecem e o resto espera, mais atrasadas primeiro', function () {
    $material = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id]);

    foreach (range(1, 12) as $day) {
        reviewNoteFor($this->token, CarbonImmutable::now()->subDays($day)->toDateString(), ['title' => "Nota {$day}"], $material);
    }

    $component = Livewire::test('revisoes-da-referencia')
        ->assertSee('12 pendentes hoje')
        ->assertSee('Mostrando 8, mais 4 aguardando.')
        ->assertSee('Nota 12')
        ->assertSee('Nota 5')
        ->assertDontSee('Nota 4');

    $oldest = ReadingNote::where('title', 'Nota 12')->sole();

    $component->call('confirmDismiss', $oldest->id)->call('dismiss')
        ->assertSee('11 pendentes hoje')
        ->assertSee('Mostrando 8, mais 3 aguardando.')
        ->assertSee('Nota 4');
});
