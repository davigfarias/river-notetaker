<?php

use App\Actions\GenerateConceptDefinition;
use App\Ai\Agents\Conceptualizer;
use App\Ai\Agents\PlainConceptualizer;
use Tests\TestCase;

uses(TestCase::class);

test('it returns one technical and one plain definition from separate agents', function () {
    Conceptualizer::fake(['Definição técnica de graça.']);
    PlainConceptualizer::fake(['Explicação simples de graça.']);

    $outcome = (new GenerateConceptDefinition)->handle('Graça');

    expect($outcome->success)->toBeTrue()
        ->and($outcome->data)->toBe([
            'definition_a' => 'Definição técnica de graça.',
            'definition_b' => 'Explicação simples de graça.',
        ]);

    Conceptualizer::assertPromptedTimes(1);
    PlainConceptualizer::assertPromptedTimes(1);
});

test('it fails when the concept is out of scope', function () {
    Conceptualizer::fake(['fora do escopo']);
    PlainConceptualizer::fake(['fora do escopo']);

    $outcome = (new GenerateConceptDefinition)->handle('Fotossíntese');

    expect($outcome->success)->toBeFalse()
        ->and($outcome->message)->toContain('fora do escopo');
});

test('it fails for a term shorter than five characters', function () {
    Conceptualizer::fake();
    PlainConceptualizer::fake();

    $outcome = (new GenerateConceptDefinition)->handle('Fé');

    expect($outcome->success)->toBeFalse();
    Conceptualizer::assertNeverPrompted();
    PlainConceptualizer::assertNeverPrompted();
});

test('it fails when an agent returns an empty definition', function () {
    Conceptualizer::fake(['Definição técnica de graça.']);
    PlainConceptualizer::fake(['   ']);

    $outcome = (new GenerateConceptDefinition)->handle('Graça');

    expect($outcome->success)->toBeFalse()
        ->and($outcome->message)->toContain('Não foi possível');
});
