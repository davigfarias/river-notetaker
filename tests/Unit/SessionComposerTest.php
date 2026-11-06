<?php

use App\DTO\SessionItem;
use App\Enums\ExerciseType;
use App\Support\SessionComposer;
use Random\Engine\Mt19937;
use Random\Randomizer;

function composer(int $seed = 1): SessionComposer
{
    return new SessionComposer(new Randomizer(new Mt19937($seed)));
}

function item(int $id, ExerciseType $type, string $group): SessionItem
{
    return new SessionItem($id, $type, $group);
}

test('é determinístico: a mesma semente dá a mesma ordem', function () {
    $items = [
        item(1, ExerciseType::RecallTitleToStatement, 'a'),
        item(2, ExerciseType::CreateExample, 'b'),
        item(3, ExerciseType::RecallStatementToTitle, 'a'),
        item(4, ExerciseType::CreateExample, 'c'),
    ];

    expect(composer(7)->compose($items))->toEqual(composer(7)->compose($items));
});

test('mantém todos os itens, sem perder nem duplicar', function () {
    $items = array_map(fn (int $i) => item($i, ExerciseType::RecallTitleToStatement, 'g'.($i % 2)), range(1, 6));

    $ids = array_map(fn (SessionItem $i) => $i->principleId, composer()->compose($items));
    sort($ids);

    expect($ids)->toBe([1, 2, 3, 4, 5, 6]);
});

test('não põe dois itens seguidos da mesma categoria nem do mesmo tipo quando é possível', function (int $seed) {
    $items = [
        item(1, ExerciseType::RecallTitleToStatement, 'a'),
        item(2, ExerciseType::RecallTitleToStatement, 'b'),
        item(3, ExerciseType::CreateExample, 'a'),
        item(4, ExerciseType::CreateExample, 'b'),
        item(5, ExerciseType::RecallStatementToTitle, 'c'),
        item(6, ExerciseType::RecallStatementToTitle, 'a'),
    ];

    $result = composer($seed)->compose($items);

    foreach (array_keys($result) as $i) {
        if ($i === 0) {
            continue;
        }
        expect($result[$i]->groupKey)->not->toBe($result[$i - 1]->groupKey)
            ->and($result[$i]->type)->not->toBe($result[$i - 1]->type);
    }
})->with([1, 2, 3, 4, 5]);

test('evita mais de 3 itens seguidos do mesmo nível de Bloom', function (int $seed) {
    $items = array_merge(
        array_map(fn (int $i) => item($i, ExerciseType::RecallTitleToStatement, 'g'.$i), range(1, 5)),
        array_map(fn (int $i) => item($i, ExerciseType::CreateExample, 'h'.$i), range(6, 10)),
    );

    $levels = array_map(fn (SessionItem $i) => $i->type->bloomLevel()->value, composer($seed)->compose($items));

    foreach (range(0, count($levels) - 4) as $start) {
        expect(count(array_unique(array_slice($levels, $start, 4))))->toBeGreaterThan(1);
    }
})->with([1, 2, 3]);

test('quando só há um tipo e uma categoria, ainda devolve tudo (restrições são "quando possível")', function () {
    $items = array_map(fn (int $i) => item($i, ExerciseType::RecallTitleToStatement, 'x'), range(1, 4));

    expect(composer()->compose($items))->toHaveCount(4);
});
