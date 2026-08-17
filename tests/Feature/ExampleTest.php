<?php

use App\Models\AccessToken;

test('returns a successful response', function () {
    $token = AccessToken::factory()->create();

    $response = $this->withSession(['access_token_id' => $token->id])->get('/');

    $response->assertOk();
});