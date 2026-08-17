<?php

use App\Models\AccessToken;

test('a guest with no session token is redirected to the login page', function () {
    $this->get('/')->assertRedirect(route('entrar'));
});

test('a valid session token is allowed through', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/')
        ->assertOk();
});

test('a revoked token in session is rejected and the session is cleared', function () {
    $token = AccessToken::factory()->revoked()->create();

    $response = $this->withSession(['access_token_id' => $token->id])->get('/');

    $response->assertRedirect(route('entrar'));
    $response->assertSessionMissing('access_token_id');
});
