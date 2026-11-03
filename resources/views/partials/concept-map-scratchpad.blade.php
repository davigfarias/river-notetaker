<div x-data="scratchpad" class="ml-auto flex items-center gap-2">
    <flux:button variant="ghost" icon="pencil-square" x-on:click="open()">Rascunho</flux:button>

    <flux:modal name="scratchpad" flyout class="md:w-[28rem]">
        <flux:heading size="lg">Rascunho</flux:heading>
        <flux:text size="sm" class="mb-4">{{ $note }}</flux:text>
        <div wire:ignore><textarea x-ref="textarea"></textarea></div>
    </flux:modal>
</div>
