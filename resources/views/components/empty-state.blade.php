@props([
    'icon',
    'heading',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col items-center justify-center rounded-xl border border-dashed border-surface-variant bg-surface-container-low px-6 py-16 text-center') }}>
    <flux:icon :name="$icon" class="mb-3 size-10 text-surface-variant-content/50" />
    <flux:heading size="md">{{ $heading }}</flux:heading>
    @if ($description)
        <flux:text class="mt-2 text-surface-variant-content">
            {{ $description }}
        </flux:text>
    @endif
    @isset($slot)
        @if (trim($slot))
            <div class="mt-4">
                {{ $slot }}
            </div>
        @endif
    @endisset
</div>
