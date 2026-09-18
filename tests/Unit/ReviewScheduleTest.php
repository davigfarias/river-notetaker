<?php

use App\Enums\Weekday;
use App\Support\ReviewSchedule;
use Carbon\CarbonImmutable;

test('a escada avança um degrau quando o aluno lembrou', function () {
    expect(ReviewSchedule::nextStage(1, true))->toBe(2)
        ->and(ReviewSchedule::nextStage(2, true))->toBe(3)
        ->and(ReviewSchedule::nextStage(3, true))->toBe(4)
        ->and(ReviewSchedule::nextStage(4, true))->toBe(5);
});

test('travar devolve a nota ao primeiro degrau em qualquer estágio', function (int $stage) {
    expect(ReviewSchedule::nextStage($stage, false))->toBe(1);
})->with([1, 2, 3, 4]);

test('o quinto estágio significa nota consolidada', function () {
    expect(ReviewSchedule::isConsolidated(5))->toBeTrue()
        ->and(ReviewSchedule::isConsolidated(4))->toBeFalse();
});

test('notas antigas com estágio zero contam como primeiro degrau', function () {
    expect(ReviewSchedule::normalizeStage(0))->toBe(1)
        ->and(ReviewSchedule::nextStage(0, true))->toBe(2);
});

test('o intervalo é medido em aulas e cai sempre no dia da aula', function (int $stage, string $expected) {
    // Quinta-feira, 17 de setembro de 2026 é uma quinta-feira de aula.
    $today = CarbonImmutable::parse('2026-09-17');

    $due = ReviewSchedule::dueDateFor($stage, Weekday::Thursday, $today);

    expect($due?->toDateString())->toBe($expected)
        ->and($due?->isoWeekday())->toBe(Weekday::Thursday->value);
})->with([
    [1, '2026-09-24'],
    [2, '2026-10-01'],
    [3, '2026-10-15'],
    [4, '2026-11-12'],
]);

test('uma nota consolidada não recebe nova data', function () {
    expect(ReviewSchedule::dueDateFor(5, Weekday::Thursday, CarbonImmutable::parse('2026-09-17')))->toBeNull();
});

test('sem dia de aula definido não há data de revisão', function () {
    expect(ReviewSchedule::dueDateFor(1, null, CarbonImmutable::parse('2026-09-17')))->toBeNull()
        ->and(ReviewSchedule::firstDueDate(null, CarbonImmutable::parse('2026-09-17')))->toBeNull();
});

test('a primeira cobrança de uma nota nova é a próxima aula', function () {
    $due = ReviewSchedule::firstDueDate(Weekday::Thursday, CarbonImmutable::parse('2026-09-15'));

    expect($due?->toDateString())->toBe('2026-09-17');
});

test('revisar no próprio dia da aula joga a cobrança para a aula seguinte', function () {
    $due = ReviewSchedule::dueDateFor(1, Weekday::Thursday, CarbonImmutable::parse('2026-09-17'));

    expect($due?->toDateString())->toBe('2026-09-24');
});

test('a próxima aula é calculada para todos os dias da semana, domingo incluído', function (Weekday $weekday) {
    // Terça-feira, 15 de setembro de 2026.
    $from = CarbonImmutable::parse('2026-09-15');

    $next = $weekday->onOrAfter($from);

    expect($next->isoWeekday())->toBe($weekday->value)
        ->and($next->greaterThanOrEqualTo($from))->toBeTrue()
        ->and($next->diffInDays($from))->toBeLessThan(7);
})->with(Weekday::cases());

test('a escada inteira funciona quando a aula é no domingo', function () {
    $due = ReviewSchedule::dueDateFor(4, Weekday::Sunday, CarbonImmutable::parse('2026-09-20'));

    // Domingo 20/09: a cobrança pula para o encontro seguinte (27/09) e soma as 7 semanas restantes.
    expect($due?->toDateString())->toBe('2026-11-15')
        ->and($due?->isoWeekday())->toBe(Weekday::Sunday->value);
});
