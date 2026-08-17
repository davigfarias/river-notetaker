<?php

use App\Actions\Orchestrators\SaveNote;
use App\DTO\NotesDTO;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;

test('a note saved through the orchestrator is stamped with the current access token', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();

    $dto = new NotesDTO(
        discipline_id: $discipline->id,
        access_token_id: $token->id,
        title: 'Nota de teste',
    );

    $outcome = app(SaveNote::class)->handle($dto);

    expect($outcome->success)->toBeTrue();
    expect(Notes::first()->access_token_id)->toBe($token->id);
});
