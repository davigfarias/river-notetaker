<?php

use App\Actions\GenerateConceptDefinition;
use App\Ai\Agents\Conceptualizer;
use App\Ai\Agents\PlainConceptualizer;

test('both conceptualizer agents share the widened scope instructions', function () {
    foreach ([new Conceptualizer, new PlainConceptualizer] as $agent) {
        $instructions = (string) $agent->instructions();

        expect($instructions)
            ->toContain('filosofia')
            ->toContain('teologia')
            ->toContain('reformada')
            ->toContain('semen religionis')
            ->toContain('Coca-Cola')
            ->toContain('fora do escopo');
    }
});

test('generateDefinition treats a formatted out-of-scope reply as out of scope', function () {
    Conceptualizer::fake(['"Fora do escopo."']);
    PlainConceptualizer::fake(['Fora do escopo.']);

    $check = app(GenerateConceptDefinition::class)->handle('Coca-Cola');

    expect($check->success)->toBeFalse()
        ->and($check->message)->toContain('escopo');
});

test('generateDefinition returns both definitions when the term is in scope', function () {
    Conceptualizer::fake(['Semente de religião implantada por Deus em toda consciência humana.']);
    PlainConceptualizer::fake(['A noção, presente em todo ser humano, de que Deus existe.']);

    $check = app(GenerateConceptDefinition::class)->handle('semen religionis');

    expect($check->success)->toBeTrue()
        ->and($check->data)->toBe([
            'definition_a' => 'Semente de religião implantada por Deus em toda consciência humana.',
            'definition_b' => 'A noção, presente em todo ser humano, de que Deus existe.',
        ]);
});
