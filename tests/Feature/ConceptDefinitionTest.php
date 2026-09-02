<?php

use App\Ai\Agents\Conceptualizer;
use Livewire\Livewire;

$twoDefinitions = "---DEFINICAO_A---\nFavor imerecido de Deus.\n---DEFINICAO_B---\nBenevolência divina concedida ao pecador.";

test('generateDefinition populates aiDefinitions from the agent', function () use ($twoDefinitions) {
    Conceptualizer::fake([$twoDefinitions]);

    $component = Livewire::test('pages::concepts')
        ->set('formConcept.term', 'Graça')
        ->call('generateDefinition')
        ->assertHasNoErrors();

    expect($component->get('aiDefinitions'))->toBe([
        'definition_a' => 'Favor imerecido de Deus.',
        'definition_b' => 'Benevolência divina concedida ao pecador.',
    ]);
});

test('selecting a definition fills the form definition', function () use ($twoDefinitions) {
    Conceptualizer::fake([$twoDefinitions]);

    $component = Livewire::test('pages::concepts')
        ->set('formConcept.term', 'Graça')
        ->call('generateDefinition')
        ->set('selectedDefinition', 'definition_a');

    expect($component->get('formConcept.definition'))->toBe('Favor imerecido de Deus.');

    $component->set('selectedDefinition', 'definition_b');

    expect($component->get('formConcept.definition'))->toBe('Benevolência divina concedida ao pecador.');
});

test('clearAiDefinitions resets the ai state and the definition field', function () use ($twoDefinitions) {
    Conceptualizer::fake([$twoDefinitions]);

    $component = Livewire::test('pages::concepts')
        ->set('formConcept.term', 'Graça')
        ->call('generateDefinition')
        ->set('selectedDefinition', 'definition_a')
        ->call('clearAiDefinitions');

    expect($component->get('aiDefinitions'))->toBeNull()
        ->and($component->get('selectedDefinition'))->toBeNull()
        ->and($component->get('formConcept.definition'))->toBe('');
});

test('generateDefinition shows an error toast when the concept is out of scope', function () {
    Conceptualizer::fake(['fora do escopo']);

    Livewire::test('pages::concepts')
        ->set('formConcept.term', 'Fotossíntese')
        ->call('generateDefinition')
        ->assertHasNoErrors();
});
