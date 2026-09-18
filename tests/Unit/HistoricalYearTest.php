<?php

use App\Enums\Era;
use App\ValueObjects\HistoricalYear;

test('the sort key orders a.C. years before d.C. years', function () {
    $keys = [
        (new HistoricalYear(500, Era::BeforeChrist))->sortKey(),
        (new HistoricalYear(44, Era::BeforeChrist))->sortKey(),
        (new HistoricalYear(1, Era::BeforeChrist))->sortKey(),
        (new HistoricalYear(1, Era::AnnoDomini))->sortKey(),
        (new HistoricalYear(30, Era::AnnoDomini))->sortKey(),
    ];

    expect($keys)->toBe([-500, -44, -1, 1, 30]);
});

test('year zero does not exist', function () {
    new HistoricalYear(0, Era::AnnoDomini);
})->throws(InvalidArgumentException::class);

test('a single year is labelled with its era', function () {
    expect((new HistoricalYear(44, Era::BeforeChrist))->label())->toBe('44 a.C.')
        ->and((string) new HistoricalYear(1517, Era::AnnoDomini))->toBe('1517 d.C.');
});

test('a range inside one era shares the era suffix', function () {
    $label = HistoricalYear::rangeLabel(
        new HistoricalYear(1337, Era::AnnoDomini),
        new HistoricalYear(1453, Era::AnnoDomini),
    );

    expect($label)->toBe('1337–1453 d.C.');
});

test('a range across eras labels both ends', function () {
    $label = HistoricalYear::rangeLabel(
        new HistoricalYear(27, Era::BeforeChrist),
        new HistoricalYear(14, Era::AnnoDomini),
    );

    expect($label)->toBe('27 a.C. – 14 d.C.');
});

test('a range without end, or ending on its start, is a single year', function () {
    $start = new HistoricalYear(1517, Era::AnnoDomini);

    expect(HistoricalYear::rangeLabel($start, null))->toBe('1517 d.C.')
        ->and(HistoricalYear::rangeLabel($start, new HistoricalYear(1517, Era::AnnoDomini)))->toBe('1517 d.C.');
});
