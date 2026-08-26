<?php

use App\Actions\ObserveCategory;
use App\Models\PastoralAdvices;

test('observeCategory returns existing categories matching a prefix, case-insensitively', function () {
    PastoralAdvices::create(['category' => 'Casamento', 'advice' => 'Conselho.']);
    PastoralAdvices::create(['category' => 'Casos difíceis', 'advice' => 'Conselho.']);
    PastoralAdvices::create(['category' => 'Ansiedade', 'advice' => 'Conselho.']);

    $check = app(ObserveCategory::class)->handle('cas');

    expect($check->success)->toBeTrue();
    expect($check->data->all())->toBe(['Casamento', 'Casos difíceis']);
});

test('observeCategory returns distinct categories only', function () {
    PastoralAdvices::create(['category' => 'Casamento', 'advice' => 'Primeiro.']);
    PastoralAdvices::create(['category' => 'casamento', 'advice' => 'Segundo.']);

    $check = app(ObserveCategory::class)->handle('casa');

    expect($check->data->all())->toBe(['Casamento']);
});

test('observeCategory respects the limit', function () {
    foreach (range(1, 3) as $index) {
        PastoralAdvices::create(['category' => "Tema {$index}", 'advice' => 'Conselho.']);
    }

    $check = app(ObserveCategory::class)->handle('tema', limit: 2);

    expect($check->data)->toHaveCount(2);
});

test('observeCategory returns an empty collection when nothing matches', function () {
    $check = app(ObserveCategory::class)->handle('inexistente');

    expect($check->success)->toBeTrue();
    expect($check->data)->toBeEmpty();
});
