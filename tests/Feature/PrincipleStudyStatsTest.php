<?php

use App\Actions\BuildPrincipleStudyStats;
use App\Actions\GetPrincipleMastery;
use App\Enums\BloomLevel;
use App\Enums\ExerciseType;
use App\Enums\SelfRating;
use App\Models\AccessToken;
use App\Models\Principle;
use App\Models\PrincipleAttempt;
use App\Models\PrincipleReviewState;
use App\Models\PrincipleTopic;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();
});

function p5Attempt(int $tokenId, int $principleId, ExerciseType $type, SelfRating $rating, ?SelfRating $ai = null, string $when = 'now'): PrincipleAttempt
{
    return PrincipleAttempt::factory()->create([
        'access_token_id' => $tokenId, 'principle_id' => $principleId, 'type' => $type, 'bloom_level' => $type->bloomLevel(),
        'self_rating' => $rating, 'ai_suggested_rating' => $ai, 'answered_at' => CarbonImmutable::parse($when),
    ]);
}

test('o domínio por nível usa só as 3 últimas respostas do nível e marca o que nunca foi praticado', function () {
    $principle = Principle::factory()->create();
    p5Attempt($this->token->id, $principle->id, ExerciseType::RecallTitleToStatement, SelfRating::Again, when: '-5 days');
    foreach (['-3 days', '-2 days', '-1 day'] as $when) {
        p5Attempt($this->token->id, $principle->id, ExerciseType::RecallTitleToStatement, SelfRating::Good, when: $when);
    }
    p5Attempt($this->token->id, $principle->id, ExerciseType::CreateExample, SelfRating::Again);

    $mastery = app(GetPrincipleMastery::class)->handle($this->token->id, [$principle->id])[$principle->id];

    expect($mastery['levels'][BloomLevel::Lembrar->value])->toBe(1.0)
        ->and($mastery['levels'][BloomLevel::Criar->value])->toBe(0.0)
        ->and($mastery['levels'][BloomLevel::Aplicar->value])->toBeNull()
        ->and($mastery['dueAt'])->toBeNull();
});

test('o domínio é isolado por token e traz a próxima revisão', function () {
    $principle = Principle::factory()->create();
    $other = AccessToken::factory()->create();
    p5Attempt($other->id, $principle->id, ExerciseType::RecallTitleToStatement, SelfRating::Easy);
    PrincipleReviewState::factory()->create(['access_token_id' => $this->token->id, 'principle_id' => $principle->id, 'due_at' => now()->addDays(5)]);

    $mastery = app(GetPrincipleMastery::class)->handle($this->token->id, [$principle->id])[$principle->id];

    expect($mastery['levels'][BloomLevel::Lembrar->value])->toBeNull()
        ->and($mastery['dueAt']->isFuture())->toBeTrue();
});

test('as estatísticas contam acerto por nível só nos últimos 30 dias e só do token', function () {
    $principle = Principle::factory()->create();
    p5Attempt($this->token->id, $principle->id, ExerciseType::ExplainWhy, SelfRating::Good);
    p5Attempt($this->token->id, $principle->id, ExerciseType::ExplainWhy, SelfRating::Again);
    p5Attempt($this->token->id, $principle->id, ExerciseType::ExplainWhy, SelfRating::Easy, when: '-40 days');
    p5Attempt(AccessToken::factory()->create()->id, $principle->id, ExerciseType::ExplainWhy, SelfRating::Easy);

    $stats = app(BuildPrincipleStudyStats::class)->handle($this->token->id, CarbonImmutable::now());

    expect($stats->answered)->toBe(2)
        ->and($stats->levels['Entender'])->toBe(['answered' => 2, 'goodRate' => 0.5])
        ->and($stats->levels['Criar'])->toBe(['answered' => 0, 'goodRate' => null]);
});

test('a concordância compara a nota sugerida pela IA com a do usuário', function () {
    $principle = Principle::factory()->create();
    p5Attempt($this->token->id, $principle->id, ExerciseType::ExplainWhy, SelfRating::Good, SelfRating::Good);
    p5Attempt($this->token->id, $principle->id, ExerciseType::ExplainWhy, SelfRating::Hard, SelfRating::Easy);
    p5Attempt($this->token->id, $principle->id, ExerciseType::ExplainWhy, SelfRating::Easy, SelfRating::Hard);
    p5Attempt($this->token->id, $principle->id, ExerciseType::RecallTitleToStatement, SelfRating::Good); // sem sugestão: fora da conta

    $agreement = app(BuildPrincipleStudyStats::class)->handle($this->token->id, CarbonImmutable::now())->agreement;

    expect($agreement)->toBe(['compared' => 3, 'same' => 1, 'aiHigher' => 1, 'aiLower' => 1]);
});

test('sem respostas não há concordância', function () {
    expect(app(BuildPrincipleStudyStats::class)->handle($this->token->id, CarbonImmutable::now())->agreement)->toBeNull();
});

test('a ordem de generosidade das notas é errei, difícil, bom, fácil', function () {
    expect(array_map(fn (SelfRating $r) => $r->score(), SelfRating::cases()))->toBe([0, 1, 2, 3]);
});

test('a tela de início explica quando ainda não há desempenho e mostra os números depois', function () {
    Livewire::test('pages::estudar-principios')
        ->assertSee('Ainda não há respostas nos últimos 30 dias.');

    $principle = Principle::factory()->create();
    p5Attempt($this->token->id, $principle->id, ExerciseType::ExplainWhy, SelfRating::Good, SelfRating::Easy);

    Livewire::test('pages::estudar-principios')
        ->assertSee('100% de acerto em 1')
        ->assertSee('ainda sem respostas')
        ->assertSee('você escolheu a mesma nota em 0')
        ->assertSee('menor em 0');
});

test('a página do tema mostra os seis marcadores de nível e a data da próxima revisão', function () {
    $topic = PrincipleTopic::factory()->create();
    $studied = Principle::factory()->create(['principle_topic_id' => $topic->id]);
    Principle::factory()->create(['principle_topic_id' => $topic->id]);
    PrincipleReviewState::factory()->create(['access_token_id' => $this->token->id, 'principle_id' => $studied->id, 'due_at' => now()->addDays(4)]);
    p5Attempt($this->token->id, $studied->id, ExerciseType::RecallTitleToStatement, SelfRating::Good);

    Livewire::test('pages::principio', ['slug' => $topic->slug])
        ->assertSee('Lembrar: bem dominado')
        ->assertSee('Aplicar: ainda não praticado')
        ->assertSee('Próxima revisão:')
        ->assertSee('Ainda não estudado');
});
