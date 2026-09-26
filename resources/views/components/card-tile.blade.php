@props([
    'href',
    'icon' => null,
    'label',
])

<div {{ $attributes->class('group border-surface-variant bg-surface-container-lowest hover:bg-surface-variant/40 relative flex min-h-40 flex-col justify-between overflow-hidden rounded-xl border p-6 shadow-sm transition-colors hover:shadow-md') }}>
    <a href="{{ $href }}" wire:navigate class="absolute inset-0 z-0" aria-label="{{ $label }}"></a>

    <div class="pointer-events-none relative z-10 flex flex-col gap-1">
        @if ($icon)
            <div class="border-outline-variant/30 bg-surface-container mb-3 flex h-12 w-12 items-center justify-center rounded-lg border">
                <flux:icon :name="$icon" class="size-6" />
            </div>
        @endif

        <flux:heading size="lg" class="group-hover:text-primary transition-colors">{{ $label }}</flux:heading>
    </div>

    @isset($slot)
        @if (trim($slot))
            <div class="pointer-events-none relative z-10">
                {{ $slot }}
            </div>
        @endif
    @endisset
</div>
