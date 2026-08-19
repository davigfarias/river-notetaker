<?php

use App\Actions\ObserveTerm;
use App\Models\Concepts;

test('observeTerm returns the existing concept when the term is already registered, case-insensitively', function () {
    $concept = Concepts::create([
        'term' => 'Justificacao',
        'definition' => 'Ato pelo qual Deus declara o pecador justo.',
    ]);

    $check = app(ObserveTerm::class)->handle('JUSTIFICACAO');

    expect($check->success)->toBeTrue();
    expect($check->data)->not->toBeNull();
    expect($check->data->id)->toBe($concept->id);
});

test('observeTerm returns null when the term is not registered', function () {
    $check = app(ObserveTerm::class)->handle('Termo inexistente');

    expect($check->success)->toBeTrue();
    expect($check->data)->toBeNull();
});
