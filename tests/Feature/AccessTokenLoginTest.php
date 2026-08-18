<?php

use App\Actions\GenerateAccessToken;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

test('a valid code logs the session in and redirects to the dashboard', function () {
    $result = app(GenerateAccessToken::class)->handle('davi-cli')->data;

    Livewire::test('pages::entrar')
        ->set('code', $result['plainTextToken'])
        ->call('entrar')
        ->assertRedirect(route('dashboard'));

    expect(session('access_token_id'))->toBe($result['token']->id);
});

test('an invalid code shows an error and does not set the session', function () {
    Livewire::test('pages::entrar')
        ->set('code', '0000')
        ->call('entrar');

    expect(session('access_token_id'))->toBeNull();
});

test('repeated invalid attempts are throttled', function () {
    RateLimiter::clear('access-token-login:127.0.0.1');

    for ($i = 0; $i < 8; $i++) {
        Livewire::test('pages::entrar')->set('code', '0000')->call('entrar');
    }

    $result = app(GenerateAccessToken::class)->handle('valido')->data;

    Livewire::test('pages::entrar')
        ->set('code', $result['plainTextToken'])
        ->call('entrar');

    expect(session('access_token_id'))->toBeNull();
});
