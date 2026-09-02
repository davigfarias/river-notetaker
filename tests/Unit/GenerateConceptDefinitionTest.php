<?php

use App\Actions\GenerateConceptDefinition;
use App\Ai\Agents\Conceptualizer;
use Tests\TestCase;

uses(TestCase::class);

test('it parses two definitions from the agent response', function () {
    Conceptualizer::fake([
        "---DEFINICAO_A---\nPrimeira definição de graça.\n---DEFINICAO_B---\nSegunda definição de graça.",
    ]);

    $outcome = (new GenerateConceptDefinition)->handle('Graça');

    expect($outcome->success)->toBeTrue()
        ->and($outcome->data)->toBe([
            'definition_a' => 'Primeira definição de graça.',
            'definition_b' => 'Segunda definição de graça.',
        ]);
});

test('it fails when the concept is out of scope', function () {
    Conceptualizer::fake(['fora do escopo']);

    $outcome = (new GenerateConceptDefinition)->handle('Fotossíntese');

    expect($outcome->success)->toBeFalse()
        ->and($outcome->message)->toContain('fora do escopo');
});

test('it fails for a term shorter than five characters', function () {
    Conceptualizer::fake();

    $outcome = (new GenerateConceptDefinition)->handle('Fé');

    expect($outcome->success)->toBeFalse();
    Conceptualizer::assertNeverPrompted();
});

test('it fails when the response cannot be parsed into two definitions', function () {
    Conceptualizer::fake(['Apenas uma linha sem delimitadores.']);

    $outcome = (new GenerateConceptDefinition)->handle('Graça');

    expect($outcome->success)->toBeFalse()
        ->and($outcome->message)->toContain('Não foi possível');
});
