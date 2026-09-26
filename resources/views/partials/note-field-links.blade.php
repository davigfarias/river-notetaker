{{-- Campo de nota (markdown) linkável a princípios por seleção de texto.
     Espera $field (nome do campo), $html (markdown já renderizado com os
     <mark> dos links existentes), $links (Collection<PrincipleNoteLink>
     desse campo) e $linkablePrinciples (só pra saber se há algo linkável —
     a lista de fato, com busca, mora no modal compartilhado
     'link-principle'). Não usa $this — tudo já vem resolvido do componente
     pai. --}}
<div
    @if ($linkablePrinciples->isNotEmpty())
        x-data="linkable(@js($field))"
        x-on:mouseup="onSelectionChange($event)"
    @endif
    class="relative"
>
    <div class="prose dark:prose-invert rounded-lg border border-surface-variant bg-surface-container-lowest p-3">
        {!! $html !!}
    </div>

    {{-- wire:ignore: o botão "+" é estado 100% client-side (posição via
         floating-ui, aberto/fechado via Popover API). Sem isso, todo
         wire:call remonta o nó e reseta a posição pro canto (0,0) do fixed. --}}
    @if ($linkablePrinciples->isNotEmpty())
        <div wire:ignore>
            <div x-ref="trigger" popover="manual" class="fixed z-50 m-0">
                <flux:button size="xs" variant="primary" square icon="plus" x-on:mousedown.prevent x-on:click="openPicker" aria-label="Linkar princípio" />
            </div>
        </div>
    @endif
</div>

@if ($links->isNotEmpty())
    <div class="mt-2 flex flex-wrap gap-2">
        @foreach ($links as $link)
            <flux:badge size="sm" color="zinc" wire:key="note-principle-link-{{ $link->id }}">
                {{ $link->principle->title ?? $link->principle->concept->term }}
                <flux:badge.close wire:click="unlinkPrincipleFromNote({{ $link->id }})" />
            </flux:badge>
        @endforeach
    </div>
@endif
