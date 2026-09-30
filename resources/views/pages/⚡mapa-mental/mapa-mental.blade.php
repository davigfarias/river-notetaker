<div class="mx-auto w-full max-w-6xl py-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ $discipline->title }}</flux:heading>
            <flux:text class="mt-1">Mapa mental</flux:text>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" icon="document-text" href="{{ route('disciplinas.show', $discipline->slug) }}" wire:navigate>Notas</flux:button>

            @if (! $editing)
                <flux:button icon="pencil-square" wire:click="startEditing">Editar</flux:button>
            @endif
        </div>
    </div>

    @if ($editing)
        <form wire:submit="save" class="space-y-4">
            <flux:field>
                <flux:textarea wire:model="mindMap" rows="16" class="font-mono" aria-label="Código mermaid" />
                <flux:error name="mindMap" />
            </flux:field>

            <div class="flex justify-end gap-2">
                @if ($discipline->mind_map)
                    <flux:button type="button" variant="ghost" wire:click="cancel">Cancelar</flux:button>
                @endif
                <flux:button type="submit" variant="primary">Salvar</flux:button>
            </div>
        </form>
    @else
        <div
            x-data="{ pz: () => $refs.box.querySelector('.mermaid')?.panZoom }"
            x-ref="box"
            x-on:fullscreenchange.window="setTimeout(() => { pz()?.resize(); pz()?.fit(); pz()?.center() }, 50)"
            class="bg-surface-container-lowest border-surface-variant flex h-[70vh] flex-col rounded-xl border [&:fullscreen]:h-screen [&:fullscreen]:rounded-none"
        >
            <div class="border-surface-variant flex items-center gap-1 border-b p-2">
                <flux:button size="sm" variant="ghost" icon="minus" square aria-label="Diminuir zoom" x-on:click="pz()?.zoomOut()" />
                <flux:button size="sm" variant="ghost" icon="plus" square aria-label="Aumentar zoom" x-on:click="pz()?.zoomIn()" />
                <flux:button size="sm" variant="ghost" icon="arrow-path" square aria-label="Ajustar à caixa" x-on:click="pz()?.resize(); pz()?.fit(); pz()?.center()" />
                <flux:text size="sm" class="ml-2 hidden sm:block">Role para dar zoom, arraste para mover.</flux:text>
                <flux:spacer />
                <flux:button size="sm" variant="ghost" icon="arrows-pointing-out" square aria-label="Tela cheia"
                    x-on:click="document.fullscreenElement ? document.exitFullscreen() : $refs.box.requestFullscreen()" />
            </div>

            <div wire:key="mind-map-{{ md5((string) $discipline->mind_map) }}" data-pan-zoom class="min-h-0 flex-1 p-2">
                <pre><code class="language-mermaid">{{ $discipline->mind_map }}</code></pre>
            </div>
        </div>
    @endif
</div>
