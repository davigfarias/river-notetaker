@props([
    'name' => null,
    'icon' => null,
    'selected' => false,
    'action' => false,
    'accent' => false,
    'size' => null,
    'disabled' => false,
])

@php
    $trailingIcon = $attributes->get('icon:trailing');
    $iconVariant = $attributes->get('icon:variant', 'micro');

    $accentClasses = $accent
        ? 'text-secondary hover:text-secondary-container group-data-[variant=default]/tabs:data-selected:border-secondary group-data-[variant=default]/tabs:data-selected:text-secondary'
        : 'text-on-surface-variant hover:text-on-surface group-data-[variant=default]/tabs:data-selected:border-on-surface group-data-[variant=default]/tabs:data-selected:text-on-surface';

    $sizeClasses = $size === 'sm' ? 'group-data-[variant=segmented]/tabs:px-2.5 group-data-[variant=segmented]/tabs:py-1 group-data-[variant=segmented]/tabs:text-xs' : '';
@endphp

<button type="button" data-flux-tab @if ($action) data-action @else role="tab" @endif
    @if ($name) data-name="{{ $name }}" @endif @if ($selected) data-initial @endif @disabled($disabled)
    @unless ($action)
        :data-selected="isSelected($el) ? '' : null"
        :aria-selected="isSelected($el)"
        :tabindex="isSelected($el) ? 0 : -1"
        @click="select($el.dataset.name)"
    @endunless
    {{ $attributes->except(['icon:trailing', 'icon:variant'])->class([
        'inline-flex shrink-0 cursor-pointer items-center gap-2 whitespace-nowrap text-sm font-medium transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary disabled:cursor-not-allowed disabled:opacity-50',
        'group-data-[variant=default]/tabs:-mb-px group-data-[variant=default]/tabs:border-b-2 group-data-[variant=default]/tabs:border-transparent group-data-[variant=default]/tabs:py-2.5',
        'group-data-[variant=segmented]/tabs:rounded-md group-data-[variant=segmented]/tabs:px-3 group-data-[variant=segmented]/tabs:py-1.5 group-data-[variant=segmented]/tabs:data-selected:bg-surface-container-lowest group-data-[variant=segmented]/tabs:data-selected:text-on-surface group-data-[variant=segmented]/tabs:data-selected:shadow-sm',
        'group-data-[variant=pills]/tabs:rounded-full group-data-[variant=pills]/tabs:px-3 group-data-[variant=pills]/tabs:py-1.5 group-data-[variant=pills]/tabs:hover:bg-on-surface/5 group-data-[variant=pills]/tabs:data-selected:bg-on-surface group-data-[variant=pills]/tabs:data-selected:text-surface',
        $accentClasses,
        $sizeClasses,
    ]) }}>
    @if ($icon)
        <flux:icon :icon="$icon" :variant="$iconVariant" class="size-4" />
    @endif

    {{ $slot }}

    @if ($trailingIcon)
        <flux:icon :icon="$trailingIcon" :variant="$iconVariant" class="size-4" />
    @endif
</button>
