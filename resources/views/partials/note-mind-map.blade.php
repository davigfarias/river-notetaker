{{--
    Canvas do mapa mental da nota (resources/js/mapa-da-nota.js).
    $map: ['nodes' => [...], 'edges' => [...]]; $mode: 'draft' (Nova nota) | 'live' (página da nota).
--}}
<div
    wire:ignore
    x-data="noteMindMap(@js(['map' => $map, 'mode' => $mode]))"
    :class="fullscreen ? 'fixed inset-0 z-50 rounded-none' : 'rounded-xl'"
    class="bg-surface-container-lowest border-surface-variant flex flex-col overflow-hidden border shadow-sm"
>
    <div class="border-surface-variant bg-surface-container-low/80 flex flex-wrap items-center gap-2 border-b p-2">
        <flux:button size="sm" variant="primary" icon="plus" type="button" x-on:click="addChild()">
            <span x-text="current ? 'Tópico filho' : 'Novo tópico'"></span>
        </flux:button>
        <flux:button size="sm" icon="arrow-uturn-left" type="button" x-on:click="undo(); focusCanvas()" x-bind:disabled="!undoStack.length">Desfazer</flux:button>

        <flux:spacer />

        @if ($mode === 'live')
            <flux:text size="sm" aria-live="polite">
                <span x-show="status === 'saving'" x-cloak>Salvando…</span>
                <span x-show="status === 'saved'" x-cloak>Salvo</span>
                <span x-show="status === 'error'" x-cloak class="text-red-600 dark:text-red-400">Não foi possível salvar. Confira a internet e mexa no mapa de novo.</span>
            </flux:text>
        @else
            <flux:text size="sm">O mapa é gravado junto com a nota, no botão Salvar.</flux:text>
        @endif

        <flux:button size="sm" type="button" x-on:click="toggleFullscreen()" x-bind:aria-label="fullscreen ? 'Sair da tela cheia' : 'Tela cheia'">
            <flux:icon.arrows-pointing-out x-show="!fullscreen" class="size-4" />
            <flux:icon.arrows-pointing-in x-show="fullscreen" x-cloak class="size-4" />
            <span x-text="fullscreen ? 'Sair da tela cheia' : 'Tela cheia'"></span>
        </flux:button>
    </div>

    <div
        x-ref="canvas"
        tabindex="0"
        aria-label="Mapa mental. Tab cria tópico filho, Enter cria tópico irmão, F2 renomeia, Delete apaga, setas navegam."
        :class="fullscreen ? 'flex-1' : 'h-[60vh]'"
        class="relative touch-none overflow-hidden outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary"
        x-on:keydown="keydown($event)"
        x-on:pointerdown="startPan($event)"
        x-on:pointermove="move($event)"
        x-on:pointerup="end($event)"
        x-on:dblclick.self="canvasDblclick($event)"
        x-on:wheel.prevent="zoom($event)"
    >
        <div
            x-show="selected" x-cloak
            x-on:pointerdown.stop
            class="bg-surface-container-lowest border-surface-variant absolute top-2 left-2 z-10 flex flex-wrap items-center gap-2 rounded-lg border p-2"
        >
            <flux:text size="sm">Seta selecionada. Arraste a bolinha para curvar.</flux:text>
            <flux:button size="sm" type="button" icon="arrows-right-left" x-on:click="reverseSelected()">Inverter direção</flux:button>
            <flux:button size="sm" type="button" variant="ghost" x-on:click="straightenSelected()">Reta</flux:button>
            <flux:button size="sm" type="button" variant="ghost" icon="trash" x-on:click="removeSelected()">Apagar seta</flux:button>
        </div>

        <div
            x-show="confirmingDelete" x-cloak
            x-on:pointerdown.stop
            role="alertdialog"
            class="absolute top-2 left-1/2 z-20 flex max-w-[calc(100%-1rem)] -translate-x-1/2 flex-wrap items-center gap-2 rounded-lg border border-red-300 bg-white p-3 shadow-lg dark:border-red-800 dark:bg-zinc-900"
        >
            <flux:text size="sm">
                Apagar “<span x-text="confirmingDelete?.label"></span>” e os
                <strong x-text="confirmingDelete?.count"></strong>
                <span x-text="confirmingDelete?.count === 1 ? 'tópico ligado abaixo dele' : 'tópicos ligados abaixo dele'"></span>?
                Dá para voltar com Desfazer.
            </flux:text>
            <flux:button size="sm" type="button" variant="danger" x-on:click="deleteNodes(confirmingDelete.key)">Apagar</flux:button>
            <flux:button size="sm" type="button" variant="ghost" x-on:click="confirmingDelete = null; focusCanvas()">Cancelar</flux:button>
        </div>

        <div x-show="!nodes.length" x-cloak class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-2 p-6 text-center">
            <flux:heading>O mapa mental está vazio.</flux:heading>
            <flux:text>Clique no botão “Novo tópico”, aqui em cima, ou dê dois cliques em qualquer lugar deste espaço para escrever o primeiro tópico.</flux:text>
        </div>

        <div class="absolute top-0 left-0 origin-top-left" :style="`transform: translate(${pan.x}px, ${pan.y}px) scale(${scale})`">
            <svg class="pointer-events-none absolute top-0 left-0 text-black dark:text-white" style="overflow: visible" width="1" height="1">
                <defs>
                    <marker id="seta" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse">
                        <path d="M0 0L10 5L0 10z" fill="currentColor" />
                    </marker>
                </defs>
                {{-- <template x-for> não funciona dentro de <svg>: as setas vão por x-html. --}}
                <g x-html="edgesMarkup()" x-on:pointerdown="edgePointerDown($event)"></g>
                <line x-show="ghostLine()" :x1="ghostLine()?.x1" :y1="ghostLine()?.y1" :x2="ghostLine()?.x2" :y2="ghostLine()?.y2" stroke="currentColor" stroke-width="2" stroke-dasharray="5 4" marker-end="url(#seta)" />
            </svg>

            <template x-for="node in nodes" :key="node.key">
                <div
                    :data-node-key="node.key"
                    x-effect="node.label; editing; $nextTick(() => measure(node, $el))"
                    x-on:pointerdown="editing !== node.key && startNode($event, node)"
                    x-on:dblclick.stop="startEdit(node)"
                    :style="`left: ${node.x}px; top: ${node.y}px`"
                    :class="current === node.key ? 'ring-2 ring-primary ring-offset-2 ring-offset-white dark:ring-offset-zinc-900' : ''"
                    class="absolute flex max-w-xs cursor-move items-center gap-2 rounded-xl bg-zinc-200 px-4 py-1.5 text-sm text-zinc-900 select-none"
                    title="Duplo clique para editar o texto"
                >
                    <span x-show="editing !== node.key" x-text="node.label" class="break-words whitespace-normal"></span>
                    <input
                        x-show="editing === node.key"
                        x-model="editText"
                        x-on:pointerdown.stop
                        x-on:keydown.stop="editKeydown($event)"
                        x-on:blur="editBlur()"
                        maxlength="200"
                        placeholder="Escreva o tópico"
                        aria-label="Texto do tópico. Enter confirma, Tab confirma e cria um filho, Esc cancela."
                        class="w-56 rounded bg-white px-1 text-zinc-900 outline-none"
                    />
                    <span
                        x-show="editing !== node.key"
                        x-on:pointerdown.stop="startEdge($event, node)"
                        class="size-4 shrink-0 cursor-crosshair rounded-full border-2 border-current opacity-60 hover:opacity-100"
                        :class="linking === node.key && 'bg-current opacity-100'"
                        title="Arraste até outro tópico para ligar, ou clique aqui e depois no outro tópico"
                        aria-label="Ligar a outro tópico"
                    ></span>
                </div>
            </template>
        </div>
    </div>

    <div class="border-surface-variant flex flex-wrap gap-x-4 gap-y-1 border-t px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400">
        <span><kbd class="font-semibold">Tab</kbd>: tópico filho</span>
        <span><kbd class="font-semibold">Enter</kbd>: tópico irmão</span>
        <span><kbd class="font-semibold">F2</kbd> ou duplo clique: editar</span>
        <span><kbd class="font-semibold">Delete</kbd>: apagar</span>
        <span><kbd class="font-semibold">Setas</kbd>: andar entre tópicos</span>
        <span><kbd class="font-semibold">Ctrl/Cmd+Z</kbd>: desfazer</span>
        <span>Bolinha do tópico: arraste para ligar</span>
    </div>
</div>
