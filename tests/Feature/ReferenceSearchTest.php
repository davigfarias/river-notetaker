<?php

use App\Actions\SearchCitations;
use App\Actions\SearchReferenceMaterials;
use App\Models\AccessToken;
use App\Models\Citation;
use App\Models\ReferenceMaterial;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->other = AccessToken::factory()->create();
});

test('works are searched by title and author and scoped to the token', function () {
    ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Ortodoxia', 'author' => 'Chesterton']);
    ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Milagres', 'author' => 'Lewis']);
    ReferenceMaterial::factory()->create(['access_token_id' => $this->other->id, 'title' => 'Ortodoxia dos outros', 'author' => 'Chesterton']);

    $results = app(SearchReferenceMaterials::class)->handle('Chesterton', $this->token->id)->data;

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Ortodoxia');
});

test('citations are searched by quote text and scoped to the token', function () {
    $mine = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id]);
    Citation::factory()->create([
        'reference_material_id' => $mine->id,
        'access_token_id' => $this->token->id,
        'quote_text' => 'O amor é paciente e generoso',
    ]);
    Citation::factory()->create([
        'reference_material_id' => $mine->id,
        'access_token_id' => $this->token->id,
        'quote_text' => 'A fé remove montanhas',
    ]);

    $foreign = ReferenceMaterial::factory()->create(['access_token_id' => $this->other->id]);
    Citation::factory()->create([
        'reference_material_id' => $foreign->id,
        'access_token_id' => $this->other->id,
        'quote_text' => 'O amor cobre multidão de pecados',
    ]);

    $results = app(SearchCitations::class)->handle('amor', $this->token->id)->data;

    expect($results->total())->toBe(1)
        ->and($results->first()->quote_text)->toBe('O amor é paciente e generoso');
});

test('an empty term yields no results', function () {
    ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id]);

    expect(app(SearchReferenceMaterials::class)->handle('', $this->token->id)->data)->toHaveCount(0);
    expect(app(SearchCitations::class)->handle('   ', $this->token->id)->data->total())->toBe(0);
});
