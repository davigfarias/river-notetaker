@props([
    'active' => false,
])

<button
    type="button"
    {{ $attributes->class([
        'rounded-full border px-3 py-1.5 text-sm transition-all',
        'border-primary bg-primary text-white' => $active,
        'border-surface-variant text-on-surface-variant hover:bg-surface-container-low' => ! $active,
    ]) }}
>
    {{ $slot }}
</button>
