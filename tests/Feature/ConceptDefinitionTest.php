<?php

use App\Ai\Agents\Conceptualizer;
use App\Ai\Agents\PlainConceptualizer;
use Livewire\Livewire;

$fakeDefinitions = function (): void {
    Conceptualizer::fake(['Favor imerecido de Deus.']);
    PlainConceptualizer::fake(['É quando Deus trata bem quem não merece.']);
};

test('generateDefinition populates aiDefinitions from both agents', function () use ($fakeDefinitions) {
    $fakeDefinitions();

    $component = Livewire::test('pages::concepts')
        ->set('formConcept.term', 'Graça')
        ->call('generateDefinition')
        ->assertHasNoErrors();

    expect($component->get('aiDefinitions'))->toBe([
        'definition_a' => 'Favor imerecido de Deus.',
        'definition_b' => 'É quando Deus trata bem quem não merece.',
    ]);
});

test('selecting a definition fills the form definition', function () use ($fakeDefinitions) {
    $fakeDefinitions();

    $component = Livewire::test('pages::concepts')
        ->set('formConcept.term', 'Graça')
        ->call('generateDefinition')
        ->set('selectedDefinition', 'definition_a');

    expect($component->get('formConcept.definition'))->toBe('Favor imerecido de Deus.');

    $component->set('selectedDefinition', 'definition_b');

    expect($component->get('formConcept.definition'))->toBe('É quando Deus trata bem quem não merece.');
});

test('clearAiDefinitions resets the ai state and the definition field', function () use ($fakeDefinitions) {
    $fakeDefinitions();

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
    PlainConceptualizer::fake(['fora do escopo']);

    Livewire::test('pages::concepts')
        ->set('formConcept.term', 'Fotossíntese')
        ->call('generateDefinition')
        ->assertHasNoErrors();
});
