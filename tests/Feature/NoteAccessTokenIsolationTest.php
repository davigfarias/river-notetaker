<?php

use App\Actions\GetDisciplineNotes;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;

test('a discipline only shows notes belonging to the requesting token', function () {
    $tokenA = AccessToken::factory()->create();
    $tokenB = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();

    $noteA = Notes::create([
        'title' => 'Nota de A',
        'discipline_id' => $discipline->id,
        'access_token_id' => $tokenA->id,
    ]);

    Notes::create([
        'title' => 'Nota de B',
        'discipline_id' => $discipline->id,
        'access_token_id' => $tokenB->id,
    ]);

    $outcome = app(GetDisciplineNotes::class)->handle($discipline->id, $tokenA->id);

    expect($outcome->data)->toHaveCount(1);
    expect($outcome->data->first()->id)->toBe($noteA->id);
});
