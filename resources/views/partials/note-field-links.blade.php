{{-- Campo de nota (markdown) linkável a princípios e comentável por seleção de texto.
     Espera $field (nome do campo), $html (markdown já renderizado com os
     <mark> dos links e comentários existentes), $links (Collection<PrincipleNoteLink>
     desse campo) e $linkablePrinciples (só pra saber se há algo linkável —
     a lista de fato, com busca, mora no modal compartilhado
     'link-principle'). Não usa $this — tudo já vem resolvido do componente
     pai. --}}
<div
    x-data="linkable(@js($field))"
    x-on:mouseup="onSelectionChange($event)"
    class="relative"
>
    <div class="prose dark:prose-invert max-w-none rounded-lg border border-surface-variant bg-surface-container-lowest p-3">
        {!! $html !!}
    </div>

    {{-- wire:ignore: o botão "+" é estado 100% client-side (posição via
         floating-ui, aberto/fechado via Popover API). Sem isso, todo
         wire:call remonta o nó e reseta a posição pro canto (0,0) do fixed. --}}
    <div wire:ignore>
        {{-- Sem classe de display aqui: `flex` venceria o display:none do
             [popover] fechado e deixaria os botões sempre visíveis. --}}
        <div x-ref="trigger" popover="manual" class="fixed z-50 m-0">
            <div class="flex gap-1">
                @if ($linkablePrinciples->isNotEmpty())
                    <flux:button size="xs" variant="primary" square icon="plus" x-on:mousedown.prevent x-on:click="openPicker" aria-label="Linkar princípio" />
                @endif
                <flux:button size="xs" variant="filled" square icon="chat-bubble-left-ellipsis" x-on:mousedown.prevent x-on:click="openComment" aria-label="Comentar trecho" />
            </div>
        </div>
    </div>
</div>

@if ($links->isNotEmpty())
    <div class="mt-2 flex flex-wrap gap-2">
        @foreach ($links as $link)
            <flux:badge size="sm" color="zinc" wire:key="note-principle-link-{{ $link->id }}">
                <button type="button" class="cursor-pointer" wire:click="viewPrinciple({{ $link->principle_id }})">
                    {{ $link->principle->title ?? $link->principle->concept->term }}
                </button>
                <flux:badge.close wire:click="unlinkPrincipleFromNote({{ $link->id }})" />
            </flux:badge>
        @endforeach
    </div>
@endif
