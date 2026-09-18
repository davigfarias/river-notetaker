<?php

use Illuminate\Support\Facades\Blade;

test('x-timeline renders the data attributes the Flux Free CSS lays out', function () {
    $html = Blade::render(<<<'BLADE'
        <x-timeline align="start">
            <x-timeline.item>
                <x-timeline.indicator color="amber">1</x-timeline.indicator>
                <x-timeline.content>Primeiro</x-timeline.content>
            </x-timeline.item>
        </x-timeline>
        BLADE);

    expect($html)
        ->toContain('data-flux-timeline ')
        ->toContain('data-flux-timeline-align="start"')
        ->toContain('data-flux-timeline-item')
        ->toContain('data-flux-timeline-gap-leading')
        ->toContain('data-flux-timeline-line-leading')
        ->toContain('data-flux-timeline-indicator')
        ->toContain('data-flux-timeline-content')
        ->toContain('data-flux-timeline-line-trailing')
        ->toContain('data-flux-timeline-gap-trailing')
        ->toContain('bg-amber-400')
        ->toContain('Primeiro');
});

test('the indicator falls back to neutral colors without a color prop', function () {
    $html = Blade::render('<x-timeline.indicator>•</x-timeline.indicator>');

    expect($html)->toContain('bg-zinc-100')
        ->not->toContain('data-color');
});

test('the timeline omits the align attribute when none is given', function () {
    expect(Blade::render('<x-timeline></x-timeline>'))->not->toContain('data-flux-timeline-align');
});
