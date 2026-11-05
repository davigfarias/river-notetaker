@props([
    'variant' => 'default',
    'size' => 'base',
    'scrollable' => false,
])

@php
    // `wire:model="prop"` names the Livewire property; the group engine reads it from data attributes.
    $modelKey = collect($attributes->getAttributes())
        ->keys()
        ->first(fn(string $key) => str_starts_with($key, 'wire:model'));
    $model = $modelKey ? $attributes->get($modelKey) : null;
    $forwardedAttributes = $modelKey ? $attributes->except([$modelKey, 'scrollable:scrollbar', 'scrollable:fade']) : $attributes->except(['scrollable:scrollbar', 'scrollable:fade']);

    $hideScrollbar = $attributes->get('scrollable:scrollbar') === 'hide';
    $fade = $attributes->has('scrollable:fade');

    $variantClasses = match ($variant) {
        'segmented' => 'inline-flex gap-0.5 rounded-lg border border-outline-variant bg-surface p-0.5',
        'pills' => 'flex gap-1',
        default => 'flex gap-6 border-b border-outline-variant',
    };

    $scrollClasses = collect([
        $scrollable ? 'overflow-x-auto' : null,
        $scrollable && $hideScrollbar ? '[scrollbar-width:none] [&::-webkit-scrollbar]:hidden' : null,
        $scrollable && $fade ? '[mask-image:linear-gradient(to_right,var(--color-on-surface)_calc(100%_-_2rem),transparent)]' : null,
    ])->filter()->implode(' ');
@endphp

<div data-flux-tabs role="tablist" data-variant="{{ $variant }}" data-size="{{ $size }}"
    @if ($model) data-model="{{ $model }}" data-live="{{ str_contains($modelKey, '.live') ? 'true' : 'false' }}" @endif
    @keydown="onKeydown($event)"
    {{ $forwardedAttributes->class(['group/tabs', $variantClasses, $scrollClasses]) }}>
    {{ $slot }}
</div>
