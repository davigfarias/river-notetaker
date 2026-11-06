<?php

use App\DTO\ReviewSnapshot;
use App\Enums\SelfRating;
use App\Support\Sm2Scheduler;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->scheduler = new Sm2Scheduler;
    $this->now = CarbonImmutable::parse('2026-11-06 10:00:00');
});

test('primeira revisão: Bom volta em 1 dia, Fácil em 3, Difícil em 1, Errei em 1 com falha', function (SelfRating $rating, int $interval, int $lapses) {
    $next = $this->scheduler->next($this->scheduler->initial(), $rating, $this->now);

    expect($next->intervalDays)->toBe($interval)
        ->and($next->lapses)->toBe($lapses)
        ->and($next->dueAt?->toDateString())->toBe($this->now->addDays($interval)->toDateString());
})->with([
    [SelfRating::Good, 1, 0],
    [SelfRating::Easy, 3, 0],
    [SelfRating::Hard, 1, 0],
    [SelfRating::Again, 1, 1],
]);

test('acertos repetidos crescem o intervalo: 1, 3, depois intervalo vezes facilidade', function () {
    $s = $this->scheduler->initial();
    $intervals = [];
    foreach (range(1, 4) as $_) {
        $s = $this->scheduler->next($s, SelfRating::Good, $this->now);
        $intervals[] = $s->intervalDays;
    }

    expect($intervals)->toBe([1, 3, 8, 20]);
});

test('errar depois de uma longa sequência zera repetições, soma falha e reduz facilidade', function () {
    $long = new ReviewSnapshot(120, 2.5, 8, 1);
    $next = $this->scheduler->next($long, SelfRating::Again, $this->now);

    expect($next->intervalDays)->toBe(1)
        ->and($next->repetitions)->toBe(0)
        ->and($next->lapses)->toBe(2)
        ->and($next->ease)->toBe(2.3);
});

test('a facilidade nunca passa do mínimo', function () {
    $s = new ReviewSnapshot(5, 1.3, 3, 0);

    expect($this->scheduler->next($s, SelfRating::Again, $this->now)->ease)->toBe(1.3)
        ->and($this->scheduler->next($s, SelfRating::Hard, $this->now)->ease)->toBe(1.3);
});

test('o intervalo respeita o máximo configurado', function () {
    $s = new ReviewSnapshot(300, 2.5, 5, 0);

    expect($this->scheduler->next($s, SelfRating::Easy, $this->now)->intervalDays)->toBe(365);
});

test('é pura: mesmo estado, nota e relógio dão sempre o mesmo resultado', function () {
    $s = new ReviewSnapshot(10, 2.5, 3, 0);

    expect($this->scheduler->next($s, SelfRating::Good, $this->now))
        ->toEqual($this->scheduler->next($s, SelfRating::Good, $this->now));
});
