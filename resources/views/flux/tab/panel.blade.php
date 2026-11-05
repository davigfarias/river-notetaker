@props(['name' => null, 'selected' => false])

@php
    $hiddenAtFirst = $selected ? null : 'hidden';
@endphp

<div data-flux-tab-panel role="tabpanel" tabindex="0" @if ($name) data-name="{{ $name }}" @endif
    @if ($selected) data-initial @endif {{ $hiddenAtFirst }}
    x-effect="syncPanel($el)" @beforematch="select($el.dataset.name)"
    {{ $attributes->class('pt-6 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary') }}>
    {{ $slot }}
</div>
