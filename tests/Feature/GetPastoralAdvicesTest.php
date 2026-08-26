<?php

use App\Actions\GetPastoralAdvices;
use App\Models\PastoralAdvices;

test('advices sharing the same category case-insensitively are grouped into one theme', function () {
    PastoralAdvices::create(['category' => 'Casamento', 'advice' => 'Primeiro conselho.']);
    PastoralAdvices::create(['category' => 'casamento', 'advice' => 'Segundo conselho.']);

    $check = app(GetPastoralAdvices::class)->handle();

    expect($check->success)->toBeTrue();
    expect($check->data->total())->toBe(1);

    $theme = $check->data->items()[0];

    expect($theme['category'])->toBe('Casamento');
    expect($theme['advices'])->toHaveCount(2);
});

test('themes are ordered alphabetically by category', function () {
    PastoralAdvices::create(['category' => 'Perseverança', 'advice' => 'Conselho.']);
    PastoralAdvices::create(['category' => 'Ansiedade', 'advice' => 'Conselho.']);
    PastoralAdvices::create(['category' => 'Casamento', 'advice' => 'Conselho.']);

    $check = app(GetPastoralAdvices::class)->handle();

    $categories = collect($check->data->items())->pluck('category')->all();

    expect($categories)->toBe(['Ansiedade', 'Casamento', 'Perseverança']);
});

test('themes are paginated 5 per page', function () {
    foreach (range(1, 7) as $index) {
        PastoralAdvices::create(['category' => "Tema {$index}", 'advice' => 'Conselho.']);
    }

    $check = app(GetPastoralAdvices::class)->handle();

    expect($check->data->count())->toBe(5);
    expect($check->data->lastPage())->toBe(2);
});

test('search matches the theme category', function () {
    PastoralAdvices::create(['category' => 'Ansiedade', 'advice' => 'Conselho sobre paz.']);
    PastoralAdvices::create(['category' => 'Casamento', 'advice' => 'Conselho sobre união.']);

    $check = app(GetPastoralAdvices::class)->handle('ansied');

    expect($check->data->total())->toBe(1);
    expect($check->data->items()[0]['category'])->toBe('Ansiedade');
});

test('search matches the advice text and returns the whole matched theme', function () {
    PastoralAdvices::create(['category' => 'Ansiedade', 'advice' => 'Lance sobre ele toda a sua ansiedade.']);
    PastoralAdvices::create(['category' => 'Ansiedade', 'advice' => 'Não andeis ansiosos por coisa alguma.']);
    PastoralAdvices::create(['category' => 'Casamento', 'advice' => 'Conselho sobre união.']);

    $check = app(GetPastoralAdvices::class)->handle('ansiosos');

    expect($check->data->total())->toBe(1);
    expect($check->data->items()[0]['category'])->toBe('Ansiedade');
    expect($check->data->items()[0]['advices'])->toHaveCount(2);
});
