<?php

use App\Models\AccessToken;
use App\Models\Citation;
use App\Models\ReferenceMaterial;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    $this->material = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id]);
});

test('a citation can be added inline on the reference detail page', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('citationForm.quote_text', 'A gratidão é a memória do coração.')
        ->set('citationForm.location', 'p. 15')
        ->call('addCitation')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('citations', [
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'quote_text' => 'A gratidão é a memória do coração.',
        'location' => 'p. 15',
    ]);
});

test('quote text is required', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('citationForm.quote_text', '')
        ->call('addCitation')
        ->assertHasErrors('citationForm.quote_text');
});

test('a citation can be edited and deleted', function () {
    $citation = Citation::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'quote_text' => 'Original',
    ]);

    $component = Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->call('editCitation', $citation->id)
        ->set('editCitationForm.quote_text', 'Editada')
        ->call('updateCitation')
        ->assertHasNoErrors();

    expect($citation->refresh()->quote_text)->toBe('Editada');

    $component->call('deleteCitation', $citation->id);

    $this->assertDatabaseMissing('citations', ['id' => $citation->id]);
});

test('a token cannot add a citation to a material it does not own', function () {
    $foreign = ReferenceMaterial::factory()->create([
        'access_token_id' => AccessToken::factory()->create()->id,
    ]);

    Livewire::test('pages::referencia', ['id' => $foreign->id])
        ->assertStatus(404);
});
