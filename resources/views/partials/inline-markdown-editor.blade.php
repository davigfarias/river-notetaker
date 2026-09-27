{{-- Editor EasyMDE in-place para um campo markdown da nota ($field).
     O @if que monta/desmonta este partial fica FORA do wire:ignore (ver
     commit 0520c4b): o morph insere o nó, o init() do markdownEditor cria o
     EasyMDE e o destroy() desfaz ao sair. O wire:key com o id da nota força
     um editor novo ao trocar de nota. --}}
<div
    wire:key="edit-{{ $field }}-{{ $noteId }}"
    x-on:keydown.escape="$wire.cancelEdit(@js($field))"
    x-on:keydown.meta.enter.prevent="$wire.updateNote(@js($field))"
    x-on:keydown.ctrl.enter.prevent="$wire.updateNote(@js($field))"
>
    <div wire:ignore>
        <div x-data="markdownEditor(@js('draft.'.$field))">
            <textarea x-ref="textarea"></textarea>
        </div>
    </div>

    <div class="mt-2 flex justify-end gap-2">
        <flux:button size="sm" variant="ghost" wire:click="cancelEdit('{{ $field }}')">Cancelar</flux:button>
        <flux:button size="sm" variant="primary" wire:click="updateNote('{{ $field }}')">Salvar</flux:button>
    </div>
</div>
