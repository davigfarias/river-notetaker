{{-- Clone do <flux:timeline.indicator> (Flux Pro): círculo com ícone, número ou texto. Classes de cor literais para o scan do Tailwind. --}}
@props([
    'color' => null,
])

@php
    $colorClasses = match ($color) {
        'red' => 'bg-red-500 text-white',
        'orange' => 'bg-orange-500 text-white',
        'amber' => 'bg-amber-400 text-amber-950',
        'yellow' => 'bg-yellow-400 text-yellow-950',
        'lime' => 'bg-lime-400 text-lime-950',
        'green' => 'bg-green-500 text-white',
        'emerald' => 'bg-emerald-500 text-white',
        'teal' => 'bg-teal-500 text-white',
        'cyan' => 'bg-cyan-500 text-white',
        'sky' => 'bg-sky-500 text-white',
        'blue' => 'bg-blue-500 text-white',
        'indigo' => 'bg-indigo-500 text-white',
        'violet' => 'bg-violet-500 text-white',
        'purple' => 'bg-purple-500 text-white',
        'fuchsia' => 'bg-fuchsia-500 text-white',
        'pink' => 'bg-pink-500 text-white',
        'rose' => 'bg-rose-500 text-white',
        default => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300',
    };
@endphp

<div {{ $attributes->class([
    'flex size-(--flux-timeline-indicator-size) shrink-0 items-center justify-center rounded-full text-xs font-medium',
    $colorClasses,
]) }} data-flux-timeline-indicator @if ($color) data-color="{{ $color }}" @endif>
    {{ $slot }}
</div>
