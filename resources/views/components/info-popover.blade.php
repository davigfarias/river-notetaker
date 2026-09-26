@props([
    'width' => 'w-72',
])

<div x-data="conceptPopover" {{ $attributes->class('relative') }}>
    <div x-ref="trigger" x-on:mouseenter="open" x-on:mouseleave="scheduleClose">
        @isset($trigger)
            {{ $trigger }}
        @else
            <button type="button" class="text-on-surface-variant hover:text-primary">
                <flux:icon name="information-circle" class="size-4" />
            </button>
        @endisset
    </div>

    <div
        x-ref="panel"
        popover="manual"
        x-on:mouseenter="open"
        x-on:mouseleave="scheduleClose"
        class="fixed z-50 m-0 {{ $width }} rounded-xl border border-surface-variant bg-surface-container p-3 text-sm leading-relaxed text-on-surface shadow-xl"
    >
        {{ $slot }}
    </div>
</div>
