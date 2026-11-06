<?php

use App\Enums\ExerciseType;
use App\Support\ExerciseTypeChooser;
use Random\Engine\Mt19937;
use Random\Randomizer;

function chooser(): ExerciseTypeChooser
{
    return new ExerciseTypeChooser(new Randomizer(new Mt19937(3)));
}

$offline = [ExerciseType::RecallTitleToStatement, ExerciseType::RecallStatementToTitle, ExerciseType::CreateExample];
$all = ExerciseType::cases();

test('exige nível alto quando a sessão precisa dele', function () use ($offline) {
    expect(chooser()->choose($offline, [], false, 0, true))->toBe(ExerciseType::CreateExample);
});

test('princípio novo começa por Recordar, Explicar ou Aplicar', function () use ($all) {
    $starters = [ExerciseType::RecallTitleToStatement, ExerciseType::ExplainWhy, ExerciseType::ApplyScenario];

    foreach (range(1, 20) as $_) {
        expect($starters)->toContain(chooser()->choose($all, [], true, 0, false));
    }
});

test('prefere o nível menos praticado', function () use ($offline) {
    $stats = [
        'lembrar' => ['count' => 5, 'last' => 100],
        'criar' => ['count' => 1, 'last' => 100],
    ];

    expect(chooser()->choose($offline, $stats, false, 0, false))->toBe(ExerciseType::CreateExample);
});

test('no empate de prática vence o nível praticado há mais tempo', function () use ($offline) {
    $stats = [
        'lembrar' => ['count' => 2, 'last' => 500],
        'criar' => ['count' => 2, 'last' => 100],
    ];

    expect(chooser()->choose($offline, $stats, false, 0, false))->toBe(ExerciseType::CreateExample);
});

test('com muitas falhas reconstrói a base (Explicar ou Recordar)', function () use ($all) {
    foreach (range(1, 10) as $_) {
        expect([ExerciseType::ExplainWhy, ExerciseType::RecallTitleToStatement])
            ->toContain(chooser()->choose($all, [], false, 3, false));
    }
});

test('sem tipos disponíveis cai em Recordar', function () {
    expect(chooser()->choose([], [], false, 0, true))->toBe(ExerciseType::RecallTitleToStatement);
});
