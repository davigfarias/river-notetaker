@props(['result', 'textAttribute'])

<div {{ $attributes }}>
    <div class="px-3 py-2 text-on-surface">
        {{ $result[$textAttribute] ?? $result }}
    </div>
</div>