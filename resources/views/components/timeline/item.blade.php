{{-- Clone do <flux:timeline.item> (Flux Pro): a ordem dos filhos segue as linhas do grid definido no flux.css (gap, linha, indicador/conteúdo, linha, gap). --}}
@props([
    'align' => null,
])

<div {{ $attributes }} data-flux-timeline-item @if ($align) data-flux-timeline-align="{{ $align }}" @endif>
    <div data-flux-timeline-gap-leading></div>
    <div data-flux-timeline-line-leading><div class="bg-zinc-200 dark:bg-zinc-700"></div></div>

    {{ $slot }}

    <div data-flux-timeline-line-trailing><div class="bg-zinc-200 dark:bg-zinc-700"></div></div>
    <div data-flux-timeline-gap-trailing></div>
</div>
