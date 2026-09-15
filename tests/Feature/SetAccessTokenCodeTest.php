<?php

use App\Actions\ValidateAccessToken;
use App\Models\AccessToken;

test('it replaces the code of an existing token and keeps its id', function () {
    $token = AccessToken::factory()->create(['token' => hash('sha256', '1234')]);

    $this->artisan('token:set-code', ['id' => $token->id, 'code' => '3011', '--force' => true])
        ->assertSuccessful();

    expect($token->fresh()->token)->toBe(hash('sha256', '3011'))
        ->and(app(ValidateAccessToken::class)->handle('3011')->data->id)->toBe($token->id)
        ->and(app(ValidateAccessToken::class)->handle('1234')->success)->toBeFalse();
});

test('it fails for a missing or revoked token', function () {
    $revoked = AccessToken::factory()->revoked()->create();

    $this->artisan('token:set-code', ['id' => 999, 'code' => '3011', '--force' => true])->assertFailed();
    $this->artisan('token:set-code', ['id' => $revoked->id, 'code' => '3011', '--force' => true])->assertFailed();
});

test('it rejects codes that are not exactly 4 digits', function (string $code) {
    $token = AccessToken::factory()->create(['token' => hash('sha256', '1234')]);

    $this->artisan('token:set-code', ['id' => $token->id, 'code' => $code, '--force' => true])->assertFailed();

    expect($token->fresh()->token)->toBe(hash('sha256', '1234'));
})->with(['12a', '123', '12345']);

test('it fails when the code is already used by another token', function () {
    AccessToken::factory()->create(['token' => hash('sha256', '3011')]);
    $token = AccessToken::factory()->create(['token' => hash('sha256', '1234')]);

    $this->artisan('token:set-code', ['id' => $token->id, 'code' => '3011', '--force' => true])->assertFailed();

    expect($token->fresh()->token)->toBe(hash('sha256', '1234'));
});
