<?php

use App\Models\AccessToken;
use App\Models\Concepts;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\PastoralAdvices;
use App\Models\ReferenceMaterial;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    $this->discipline = Disciplines::factory()->create();
    $this->note = Notes::create([
        'title' => 'A graça de Deus',
        'discipline_id' => $this->discipline->id,
        'access_token_id' => $this->token->id,
        'tags' => ['graça', 'soteriologia'],
        'impressions' => 'Impressão marcante sobre o tema.',
        'life_experiences' => 'Uma experiência ligada a isso.',
    ]);
});

test('the route downloads a markdown file with the note content', function () {
    Concepts::create(['note_id' => $this->note->id, 'term' => 'Graça', 'definition' => 'Favor imerecido de Deus.']);
    PastoralAdvices::create(['note_id' => $this->note->id, 'category' => 'Aconselhamento', 'advice' => 'Ouvir antes de responder.']);
    $reference = ReferenceMaterial::factory()->create(['title' => 'Institutas', 'author' => 'Calvino', 'year' => 1536]);
    $this->note->referenceMaterials()->attach($reference->id);

    $response = $this->get(route('notas.exportar', $this->note->id));

    $response->assertOk()
        ->assertHeader('content-type', 'text/markdown; charset=utf-8')
        ->assertHeader('content-disposition', 'attachment; filename='.Str::slug($this->note->title).'.md');

    $content = $response->streamedContent();

    expect($content)
        ->toContain('# A graça de Deus')
        ->toContain('#graça #soteriologia')
        ->toContain('- **Graça**: Favor imerecido de Deus.')
        ->toContain('- **Aconselhamento**: Ouvir antes de responder.')
        ->toContain('## Impressões')
        ->toContain('Impressão marcante sobre o tema.')
        ->toContain('## Experiências de Vida')
        ->toContain('Uma experiência ligada a isso.')
        ->toContain('- Institutas — Calvino (1536)');
});

test('the route forbids a note belonging to another access token', function () {
    $this->note->update(['access_token_id' => AccessToken::factory()->create()->id]);

    $this->get(route('notas.exportar', $this->note->id))->assertForbidden();
});
