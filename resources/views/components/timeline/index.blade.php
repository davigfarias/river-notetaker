{{-- Clone do <flux:timeline> (Flux Pro), só vertical: o layout (grid, subgrid, linhas, align) vem pronto do flux.css do Flux Free, via [data-flux-timeline*]. --}}
@props([
    'align' => null,
])

<div {{ $attributes }} data-flux-timeline @if ($align) data-flux-timeline-align="{{ $align }}" @endif>
    {{ $slot }}
</div>
