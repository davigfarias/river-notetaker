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

test('the layout renders flux modals before the flux script so ui-close finds its button on mount', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/')
        ->assertOk()
        ->assertSeeInOrder(['data-modal="global-token-expiration"', '/flux/flux'], false);
});
