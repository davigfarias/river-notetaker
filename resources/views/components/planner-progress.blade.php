@props([
    'read' => 0,
    'total' => 0,
    'height' => 'h-2.5',
])

{{-- Um segmento por item: cada check acende um pedaço da barra. Com muitos itens vira uma barra contínua. --}}
@php($percent = $total === 0 ? 0 : (int) round($read / $total * 100))

<div
    role="progressbar"
    aria-valuemin="0"
    aria-valuemax="{{ $total }}"
    aria-valuenow="{{ $read }}"
    aria-label="{{ $read }} de {{ $total }} lidos"
    {{ $attributes->class('flex w-full gap-1') }}
>
    @if ($total === 0)
        <div class="{{ $height }} bg-surface-variant/60 w-full rounded-sm"></div>
    @elseif ($total > 60)
        <div class="{{ $height }} bg-surface-variant w-full overflow-hidden rounded-sm">
            <div class="bg-primary h-full rounded-sm transition-[width] duration-500 motion-reduce:transition-none" style="width: {{ $percent }}%"></div>
        </div>
    @else
        @for ($i = 0; $i < $total; $i++)
            <div @class([
                $height,
                'min-w-0 flex-1 rounded-sm transition-colors duration-300 motion-reduce:transition-none',
                'bg-primary' => $i < $read,
                'bg-surface-variant' => $i >= $read,
            ])></div>
        @endfor
    @endif
</div>
