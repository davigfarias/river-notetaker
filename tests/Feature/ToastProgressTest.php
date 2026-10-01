<?php

use App\Models\AccessToken;

test('the layout toast carries the progress fill and keeps the flux show call first', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/')
        ->assertOk()
        ->assertSee('data-toast-fill', false)
        ->assertSee('data-toast-icon', false)
        ->assertSee('$el.showToast($event.detail); try {', false);
});
