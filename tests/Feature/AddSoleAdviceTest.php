<?php

use App\Models\AccessToken;
use App\Models\PastoralAdvices;
use Livewire\Livewire;

test('a sole advice can be created without an associated note', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id]);

    Livewire::test('pages::pastoral')
        ->set('formAdvice.category', 'Perdão')
        ->set('formAdvice.advice', 'Perdoai, como também Deus vos perdoou em Cristo.')
        ->call('addSoleAdvice')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('pastoral_advices', [
        'category' => 'Perdão',
        'advice' => 'Perdoai, como também Deus vos perdoou em Cristo.',
        'note_id' => null,
    ]);

    expect(PastoralAdvices::first()->note_id)->toBeNull();
});
