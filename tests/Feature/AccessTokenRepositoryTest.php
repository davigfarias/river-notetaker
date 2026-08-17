<?php

use App\Models\AccessToken;
use App\Repository\AppRepository;

test('createAccessToken returns the plaintext code once and stores only its hash', function () {
    $repository = app(AppRepository::class);

    $result = $repository->createAccessToken('davi-cli');

    expect($result['plainTextToken'])->toMatch('/^\d{4}$/');
    expect($result['token']->name)->toBe('davi-cli');

    $stored = AccessToken::first();
    expect($stored->token)->toBe(hash('sha256', $result['plainTextToken']));
});

test('findValidAccessTokenByPlainText finds an active token and misses a revoked or unknown one', function () {
    $repository = app(AppRepository::class);

    $result = $repository->createAccessToken('ativo');
    $revoked = AccessToken::factory()->revoked()->create();

    expect($repository->findValidAccessTokenByPlainText($result['plainTextToken']))->not->toBeNull();
    expect($repository->findValidAccessTokenByPlainText('0000'))->toBeNull();

    $revokedPlainText = '9999';
    $revoked->update(['token' => hash('sha256', $revokedPlainText)]);
    expect($repository->findValidAccessTokenByPlainText($revokedPlainText))->toBeNull();
});

test('revokeAccessToken sets revoked_at and is idempotent', function () {
    $repository = app(AppRepository::class);
    $token = AccessToken::factory()->create();

    expect($repository->revokeAccessToken($token->id))->toBeTrue();
    expect($token->fresh()->revoked_at)->not->toBeNull();

    expect($repository->revokeAccessToken($token->id))->toBeFalse();
});
