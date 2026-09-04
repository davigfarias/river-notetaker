<?php

use App\Actions\SearchGlobal;
use App\Enums\SearchResultType;
use App\Models\AccessToken;
use App\Models\Citation;
use App\Models\Concepts;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\PastoralAdvices;
use App\Models\ReferenceMaterial;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->other = AccessToken::factory()->create();
});

test('notes, reference materials and citations are scoped to the token', function () {
    $discipline = Disciplines::factory()->create();

    Notes::create(['access_token_id' => $this->token->id, 'discipline_id' => $discipline->id, 'title' => 'Graça e Liberdade', 'tags' => []]);
    Notes::create(['access_token_id' => $this->other->id, 'discipline_id' => $discipline->id, 'title' => 'Graça alheia', 'tags' => []]);

    $reference = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id, 'title' => 'A Graça Suprema']);
    ReferenceMaterial::factory()->create(['access_token_id' => $this->other->id, 'title' => 'Graça de outro']);

    Citation::factory()->create(['access_token_id' => $this->token->id, 'reference_material_id' => $reference->id, 'quote_text' => 'A graça nos alcança']);
    Citation::factory()->create(['access_token_id' => $this->other->id, 'reference_material_id' => $reference->id, 'quote_text' => 'A graça de outro alcança']);

    $results = app(SearchGlobal::class)->handle('graça', $this->token->id)->data;

    $notes = $results->where('type', SearchResultType::Nota);
    $references = $results->where('type', SearchResultType::Referencia);
    $citations = $results->where('type', SearchResultType::Citacao);

    expect($notes)->toHaveCount(1)->and($notes->first()->title)->toBe('Graça e Liberdade')
        ->and($references)->toHaveCount(1)->and($references->first()->title)->toBe('A Graça Suprema')
        ->and($citations)->toHaveCount(1);
});

test('pastoral advices and concepts are not scoped to a token', function () {
    PastoralAdvices::create(['category' => 'Luto', 'advice' => 'Chorar com os que choram é bíblico.']);
    Concepts::create(['term' => 'Consolo', 'definition' => 'O ato de trazer conforto em meio à dor.']);

    $results = app(SearchGlobal::class)->handle('choram', $this->token->id)->data;

    expect($results->where('type', SearchResultType::ConselhoPastoral))->toHaveCount(1);
});

test('an empty term yields no results', function () {
    Notes::factory()->create(['access_token_id' => $this->token->id]);

    expect(app(SearchGlobal::class)->handle('', $this->token->id)->data)->toHaveCount(0);
    expect(app(SearchGlobal::class)->handle('   ', $this->token->id)->data)->toHaveCount(0);
});
