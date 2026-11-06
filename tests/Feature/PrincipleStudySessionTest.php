<?php

use App\Actions\Orchestrators\CompleteExerciseReviewOrchestrator;
use App\Actions\Orchestrators\StartPrincipleSessionOrchestrator;
use App\Actions\Orchestrators\SubmitExerciseAnswerOrchestrator;
use App\Enums\ExerciseType;
use App\Enums\SelfRating;
use App\Models\AccessToken;
use App\Models\Concepts;
use App\Models\Disciplines;
use App\Models\Principle;
use App\Models\PrincipleAttempt;
use App\Models\PrincipleCategory;
use App\Models\PrincipleReviewState;
use App\Models\PrincipleSession;
use App\Models\PrincipleTopic;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();
});

function startSession(int $tokenId, array $args = []): PrincipleSession
{
    $check = app(StartPrincipleSessionOrchestrator::class)->handle($tokenId, ...$args);
    expect($check->success)->toBeTrue();

    return $check->data;
}

test('sem princípios a sessão não começa e explica por quê', function () {
    $check = app(StartPrincipleSessionOrchestrator::class)->handle($this->token->id);

    expect($check->success)->toBeFalse()
        ->and($check->message)->toBe('Não há princípios para estudar agora.');
});

test('a sessão usa só o limite de novos por dia e grava a composição', function () {
    Principle::factory()->count(5)->create();

    $session = startSession($this->token->id);

    expect($session->planned_items)->toBe(3)
        ->and($session->composition)->toHaveCount(3)
        ->and($session->access_token_id)->toBe($this->token->id);
});

test('princípios vencidos entram antes dos novos e os que não venceram ficam de fora', function () {
    $due = Principle::factory()->create();
    $later = Principle::factory()->create();
    PrincipleReviewState::factory()->due()->create(['access_token_id' => $this->token->id, 'principle_id' => $due->id]);
    PrincipleReviewState::factory()->create(['access_token_id' => $this->token->id, 'principle_id' => $later->id]);

    $ids = collect(startSession($this->token->id)->composition)->pluck('principle_id');

    expect($ids)->toContain($due->id)->not->toContain($later->id);

    $withUpcoming = collect(startSession($this->token->id, ['includeNotDue' => true])->composition)->pluck('principle_id');

    expect($withUpcoming)->toContain($later->id);
});

test('a mistura de níveis respeita a proporção mínima de nível alto', function () {
    PrincipleReviewState::factory()->due()->count(5)->create(['access_token_id' => $this->token->id]);

    $types = collect(startSession($this->token->id)->composition)->map(fn ($i) => ExerciseType::from($i['type']));
    $higher = $types->filter(fn (ExerciseType $t) => $t->bloomLevel()->isHigherOrder())->count();

    expect($higher)->toBeGreaterThanOrEqual((int) ceil(0.5 * $types->count()));
});

test('princípio do tipo Concept estuda com termo e definição do conceito', function () {
    $concept = Concepts::create(['term' => 'Justificação', 'definition' => 'Declaração de justo diante de Deus.']);
    Principle::factory()->forConcept($concept->id)->create();
    $session = startSession($this->token->id);

    Livewire::test('pages::estudar-principios')
        ->assertSet('sessionId', $session->id)
        ->assertSee('Justificação')
        ->assertDontSee('Declaração de justo diante de Deus.')
        ->set('answer', 'Meu palpite')
        ->call('reveal')
        ->assertSee('Declaração de justo diante de Deus.');
});

test('fluxo completo: responder, avaliar, agendar e ver o resumo', function () {
    Principle::factory()->count(2)->create();

    $component = Livewire::test('pages::estudar-principios')
        ->assertSet('step.value', 'setup')
        ->call('start')
        ->assertSet('step.value', 'question');

    $planned = PrincipleSession::first()->planned_items;

    foreach (range(1, $planned) as $_) {
        $component->set('answer', 'Minha resposta')->call('reveal')->assertSet('step.value', 'feedback')->call('rate', 'good');
    }

    $component->assertSet('step.value', 'summary')->assertSee('Sessão concluída');

    expect(PrincipleSession::first()->finished_at)->not->toBeNull()
        ->and(PrincipleReviewState::where('access_token_id', $this->token->id)->count())->toBe($planned)
        ->and(PrincipleAttempt::whereNotNull('self_rating')->count())->toBe($planned);
});

test('o gabarito só aparece depois de o usuário tentar', function () {
    $principle = Principle::factory()->create(['title' => 'Cristo é a chave', 'body' => 'Interprete tudo à luz de Cristo.']);
    $session = startSession($this->token->id);
    $type = $session->composition[0]['type'];

    $component = Livewire::test('pages::estudar-principios');

    if ($type === ExerciseType::RecallStatementToTitle->value) {
        $component->assertDontSee('Cristo é a chave');
    } elseif ($type === ExerciseType::RecallTitleToStatement->value) {
        $component->assertDontSee('Interprete tudo à luz de Cristo.');
    }

    $component->set('answer', 'tentativa')->call('reveal')->assertSee('Interprete tudo à luz de Cristo.');
});

test('exercício que exige resposta não revela o gabarito com resposta vazia', function () {
    Principle::factory()->create();
    $session = startSession($this->token->id);
    $session->update(['composition' => [['principle_id' => Principle::first()->id, 'type' => ExerciseType::CreateExample->value, 'retry' => false]]]);

    Livewire::test('pages::estudar-principios')
        ->set('answer', '   ')
        ->call('reveal')
        ->assertSet('step.value', 'question');

    expect(PrincipleAttempt::count())->toBe(0);
});

test('Errei reinsere o item no fim uma única vez', function () {
    Principle::factory()->create();
    $session = startSession($this->token->id);
    $principleId = $session->composition[0]['principle_id'];

    $component = Livewire::test('pages::estudar-principios')
        ->set('answer', 'x')->call('reveal')->call('rate', 'again')
        ->assertSet('step.value', 'question');

    expect($session->fresh()->composition)->toHaveCount(2)
        ->and($session->fresh()->composition[1]['retry'])->toBeTrue();

    $component->set('answer', 'x')->call('reveal')->call('rate', 'again')
        ->assertSet('step.value', 'summary');

    expect($session->fresh()->composition)->toHaveCount(2)
        ->and(PrincipleReviewState::where('principle_id', $principleId)->first()->lapses)->toBe(2);
});

test('recarregar a página retoma do próximo item pendente', function () {
    Principle::factory()->count(2)->create();
    $session = startSession($this->token->id);

    Livewire::test('pages::estudar-principios')->set('answer', 'x')->call('reveal')->call('rate', 'good');

    Livewire::test('pages::estudar-principios')
        ->assertSet('sessionId', $session->id)
        ->assertSet('index', 1)
        ->assertSet('step.value', 'question');
});

test('sair e encerrar mantém o que foi avaliado e mostra o resumo', function () {
    Principle::factory()->count(2)->create();
    startSession($this->token->id);

    Livewire::test('pages::estudar-principios')
        ->set('answer', 'x')->call('reveal')->call('rate', 'good')
        ->call('finishNow')
        ->assertSet('step.value', 'summary');

    expect(PrincipleSession::first()->finished_at)->not->toBeNull()
        ->and(PrincipleReviewState::count())->toBe(1);
});

test('"Revisar apenas os que errei" cria nova sessão só com esses princípios', function () {
    Principle::factory()->count(2)->create();
    startSession($this->token->id);

    $component = Livewire::test('pages::estudar-principios');
    $missed = PrincipleSession::first()->composition[0]['principle_id'];

    $component->set('answer', 'x')->call('reveal')->call('rate', 'again')
        ->set('answer', 'x')->call('reveal')->call('rate', 'good')
        ->set('answer', 'x')->call('reveal')->call('rate', 'good');

    if ($component->get('step.value') !== 'summary') {
        $component->set('answer', 'x')->call('reveal')->call('rate', 'good');
    }

    $component->assertSet('step.value', 'summary')->call('reviewMissed')->assertSet('step.value', 'question');

    $second = PrincipleSession::latest('id')->first();
    expect(collect($second->composition)->pluck('principle_id')->unique()->all())->toBe([$missed]);
});

test('uma resposta enorme é recusada com mensagem clara', function () {
    Principle::factory()->create();
    $session = startSession($this->token->id);
    $session->update(['composition' => [['principle_id' => Principle::first()->id, 'type' => ExerciseType::CreateExample->value, 'retry' => false]]]);

    $check = app(SubmitExerciseAnswerOrchestrator::class)->handle($this->token->id, $session->id, 0, str_repeat('a', 5001), 100);

    expect($check->success)->toBeFalse()->and($check->message)->toContain('longa demais');
});

test('isolamento: outro token não avalia nem responde na minha sessão', function () {
    Principle::factory()->create();
    $session = startSession($this->token->id);
    $other = AccessToken::factory()->create();

    $answer = app(SubmitExerciseAnswerOrchestrator::class)->handle($other->id, $session->id, 0, 'x', 100);
    $attempt = PrincipleAttempt::factory()->create(['access_token_id' => $this->token->id, 'principle_session_id' => $session->id, 'self_rating' => null]);
    $rate = app(CompleteExerciseReviewOrchestrator::class)->handle($other->id, $attempt->id, SelfRating::Easy);

    expect($answer->success)->toBeFalse()
        ->and($rate->success)->toBeFalse()
        ->and($attempt->fresh()->self_rating)->toBeNull()
        ->and(PrincipleReviewState::where('access_token_id', $other->id)->count())->toBe(0);
});

test('o estudo de um token não aparece para outro', function () {
    Principle::factory()->count(2)->create();
    startSession($this->token->id);
    $other = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $other->id]);

    Livewire::test('pages::estudar-principios')
        ->assertSet('sessionId', null)
        ->assertSet('step.value', 'setup');
});

test('a tela de estudo e o botão na aba Princípios carregam', function () {
    Principle::factory()->count(2)->create();

    $this->get(route('principios'))->assertOk()->assertSee('Estudar princípios')->assertSee('2 para hoje');
    $this->get(route('principios.estudar'))->assertOk()->assertSee('Começar sessão');
});

test('estado vazio explica quando não há nada para estudar', function () {
    Livewire::test('pages::estudar-principios')
        ->assertSee('Nenhum princípio neste recorte.')
        ->assertSeeHtml('disabled');
});

test('os cartões de disciplina e categoria mostram quantos princípios há em cada escolha', function () {
    $discipline = Disciplines::factory()->create();
    $linked = PrincipleTopic::factory()->create();
    $linked->disciplines()->attach($discipline->id);
    $category = PrincipleCategory::factory()->create(['principle_topic_id' => $linked->id]);
    Principle::factory()->count(2)->create(['principle_topic_id' => $linked->id, 'principle_category_id' => $category->id]);
    Principle::factory()->count(3)->create();

    Livewire::test('pages::estudar-principios')
        ->assertSee('Sem filtro: 5 princípios no total.')
        ->set('disciplineId', (string) $discipline->id)
        ->assertSee('Filtro ativo: 2 princípios nesta disciplina.')
        ->assertSee('Neste recorte há 2 princípios.')
        ->assertSee('Para estudar hoje: 2 princípios.')
        ->set('categoryId', (string) $category->id)
        ->assertSee('Filtro ativo: 2 princípios nesta categoria.')
        ->set('disciplineId', 'abc')
        ->assertSee('Sem filtro: 5 princípios no total.');
});
