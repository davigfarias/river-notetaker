<?php

use App\Enums\BloomLevel;
use App\Enums\ExerciseType;

test('a ordem dos níveis de Bloom é crescente e nível alto começa em Aplicar', function () {
    $orders = array_map(fn (BloomLevel $l) => $l->order(), BloomLevel::cases());

    expect($orders)->toBe([1, 2, 3, 4, 5, 6])
        ->and(BloomLevel::Entender->isHigherOrder())->toBeFalse()
        ->and(BloomLevel::Aplicar->isHigherOrder())->toBeTrue()
        ->and(BloomLevel::Criar->label())->toBe('Criar');
});

test('cada tipo de exercício mapeia para o nível certo', function (ExerciseType $type, BloomLevel $level) {
    expect($type->bloomLevel())->toBe($level);
})->with([
    [ExerciseType::RecallTitleToStatement, BloomLevel::Lembrar],
    [ExerciseType::RecallStatementToTitle, BloomLevel::Lembrar],
    [ExerciseType::ExplainWhy, BloomLevel::Entender],
    [ExerciseType::ApplyScenario, BloomLevel::Aplicar],
    [ExerciseType::DistinguishPrinciples, BloomLevel::Analisar],
    [ExerciseType::EvaluateInterpretation, BloomLevel::Avaliar],
    [ExerciseType::CreateExample, BloomLevel::Criar],
]);

test('só 3 tipos precisam de IA para gerar e só os de recordar dispensam IA para avaliar', function () {
    $generate = array_filter(ExerciseType::cases(), fn (ExerciseType $t) => $t->requiresAiGeneration());
    $noEvaluation = array_filter(ExerciseType::cases(), fn (ExerciseType $t) => ! $t->requiresAiEvaluation());

    expect($generate)->toHaveCount(3)
        ->and($noEvaluation)->toHaveCount(2)
        ->and(ExerciseType::CreateExample->requiresAiGeneration())->toBeFalse();
});
