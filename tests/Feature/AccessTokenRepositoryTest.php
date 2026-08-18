<?php

use App\Actions\GenerateAccessToken;
use App\Actions\RevokeAccessToken;
use App\Actions\ValidateAccessToken;
use App\Models\AccessToken;

test('createAccessToken returns the plaintext code once and stores only its hash', function () {
    $result = app(GenerateAccessToken::class)->handle('davi-cli')->data;

    expect($result['plainTextToken'])->toMatch('/^\d{4}$/');
    expect($result['token']->name)->toBe('davi-cli');

    $stored = AccessToken::first();
    expect($stored->token)->toBe(hash('sha256', $result['plainTextToken']));
});

test('findValidAccessTokenByPlainText finds an active token and misses a revoked or unknown one', function () {
    $result = app(GenerateAccessToken::class)->handle('ativo')->data;
    $revoked = AccessToken::factory()->revoked()->create();

    expect(app(ValidateAccessToken::class)->handle($result['plainTextToken'])->success)->toBeTrue();
    expect(app(ValidateAccessToken::class)->handle('0000')->success)->toBeFalse();

    $revokedPlainText = '9999';
    $revoked->update(['token' => hash('sha256', $revokedPlainText)]);
    expect(app(ValidateAccessToken::class)->handle($revokedPlainText)->success)->toBeFalse();
});

test('revokeAccessToken sets revoked_at and is idempotent', function () {
    $token = AccessToken::factory()->create();

    expect(app(RevokeAccessToken::class)->handle($token->id)->success)->toBeTrue();
    expect($token->fresh()->revoked_at)->not->toBeNull();

    expect(app(RevokeAccessToken::class)->handle($token->id)->success)->toBeFalse();
});
