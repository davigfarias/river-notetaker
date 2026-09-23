<?php

use App\Models\AccessToken;
use App\Models\Concepts;
use Livewire\Livewire;

beforeEach(function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    Livewire::withoutLazyLoading();
});

test('linking two concepts creates the relation in both directions and shows up in the graph', function () {
    $a = Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido.']);
    $b = Concepts::create(['term' => 'Fé', 'definition' => 'Confiança em Cristo.']);

    $component = Livewire::test('pages::concepts')
        ->set('editingConceptId', $a->id)
        ->call('linkConcept', $b->id);

    $graph = $component->get('graphData');

    expect($graph['nodes'])->toHaveCount(2)
        ->and($graph['edges'])->toHaveCount(1);

    $this->assertDatabaseHas('concept_concept', ['concept_id' => $a->id, 'related_concept_id' => $b->id]);
    $this->assertDatabaseHas('concept_concept', ['concept_id' => $b->id, 'related_concept_id' => $a->id]);

    expect($component->get('linkedConcepts')->pluck('term')->all())->toContain('Fé');
});

test('unlinking removes the relation in both directions', function () {
    $a = Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido.']);
    $b = Concepts::create(['term' => 'Fé', 'definition' => 'Confiança em Cristo.']);

    Livewire::test('pages::concepts')
        ->set('editingConceptId', $a->id)
        ->call('linkConcept', $b->id)
        ->call('unlinkConcept', $b->id);

    $this->assertDatabaseMissing('concept_concept', ['concept_id' => $a->id, 'related_concept_id' => $b->id]);
    $this->assertDatabaseMissing('concept_concept', ['concept_id' => $b->id, 'related_concept_id' => $a->id]);
});

test('linking a concept keeps the search open and excludes the newly linked concept from results', function () {
    $a = Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido.']);
    $b = Concepts::create(['term' => 'Fé', 'definition' => 'Confiança em Cristo.']);

    $component = Livewire::test('pages::concepts')
        ->set('editingConceptId', $a->id)
        ->set('relatedSearch', 'F')
        ->call('linkConcept', $b->id);

    $component->assertSet('relatedSearch', 'F');

    expect($component->get('linkableResults')->pluck('id')->all())->not->toContain($b->id);
});

test('a concept cannot be linked to itself', function () {
    $a = Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido.']);

    Livewire::test('pages::concepts')
        ->set('editingConceptId', $a->id)
        ->call('linkConcept', $a->id);

    $this->assertDatabaseMissing('concept_concept', ['concept_id' => $a->id, 'related_concept_id' => $a->id]);
});
