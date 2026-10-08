<div class="w-full py-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ $discipline->title }}</flux:heading>
            <flux:text class="mt-1">Mapa de conceitos</flux:text>
        </div>

        <div x-data="scratchpad" class="ml-auto flex items-center gap-2">
            <flux:button variant="ghost" icon="pencil-square" x-on:click="open()">Rascunho</flux:button>

            <flux:modal name="scratchpad" flyout class="md:w-[28rem]">
                <flux:heading size="lg">Rascunho</flux:heading>
                <flux:text size="sm" class="mb-4">Só em memória: some ao sair da página.</flux:text>
                <div wire:ignore><textarea x-ref="textarea"></textarea></div>
            </flux:modal>
        </div>

        <flux:button variant="ghost" icon="document-text" href="{{ route('disciplinas.show', $discipline->slug) }}" wire:navigate>Notas</flux:button>
    </div>

    <div
        wire:ignore
        x-data="conceptMap(@js(['concepts' => $this->concepts, 'nodes' => $this->nodes, 'edges' => $this->edges]))"
        x-on:keydown.escape.window="cancelLink(); selected = null"
        x-on:keydown.delete.window="!$event.target.matches('input, textarea') && removeSelected()"
        class="bg-surface-container-lowest border-surface-variant flex flex-col rounded-xl border"
    >
        <div class="border-surface-variant flex min-h-12 flex-wrap items-center gap-2 border-b p-2">
            <template x-for="[key, label] in pills" :key="key">
                <span
                    draggable="true"
                    x-on:dragstart="dragPill($event, key)"
                    x-text="label"
                    class="bg-primary/15 text-primary cursor-grab rounded-full px-3 py-1 text-sm select-none"
                ></span>
            </template>
            <div class="ml-auto flex items-center gap-1">
                <flux:input size="sm" x-model="newLabel" x-on:keydown.enter.prevent="addCustom()" placeholder="Novo conceito livre" maxlength="60" aria-label="Novo conceito livre" />
                <flux:button size="sm" icon="plus" square aria-label="Adicionar conceito livre" x-on:click="addCustom()" />
            </div>
            <flux:text size="sm" x-show="!pills.length">
                <span x-show="!Object.keys(concepts).length">Marque conceitos nas notas com <code>&#123;&#123;no: Conceito&#125;&#125;</code>.</span>
                <span x-show="Object.keys(concepts).length">Todos os conceitos já estão no mapa.</span>
            </flux:text>
        </div>

        <div
            x-ref="canvas"
            class="relative h-[60vh] touch-none overflow-hidden"
            x-on:dragover.prevent
            x-on:drop.prevent="dropPill($event)"
            x-on:pointerdown="startPan($event)"
            x-on:pointermove="move($event)"
            x-on:pointerup="end($event)"
            x-on:wheel.prevent="zoom($event)"
        >
            <div
                x-show="selected" x-cloak
                x-on:pointerdown.stop
                class="bg-surface-container-lowest border-surface-variant absolute top-2 left-2 z-10 flex flex-wrap items-center gap-2 rounded-lg border p-2"
            >
                <flux:text size="sm">Arraste a bolinha para curvar.</flux:text>
                <flux:button size="sm" icon="arrows-right-left" x-on:click="reverseSelected()">Inverter direção</flux:button>
                <flux:button size="sm" variant="ghost" x-on:click="straightenSelected()">Reta</flux:button>
                <flux:button size="sm" variant="ghost" icon="trash" x-on:click="removeSelected()">Remover</flux:button>
            </div>

            <div class="absolute top-0 left-0 origin-top-left" :style="`transform: translate(${pan.x}px, ${pan.y}px) scale(${scale})`">
                <svg class="pointer-events-none absolute top-0 left-0 text-black dark:text-white" style="overflow: visible" width="1" height="1">
                    <defs>
                        <marker id="seta" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse">
                            <path d="M0 0L10 5L0 10z" fill="currentColor" />
                        </marker>
                    </defs>
                    {{-- <template x-for> não funciona dentro de <svg> (o parse cria elementos SVG, não template): as setas vão por x-html. --}}
                    <g
                        x-html="edgesMarkup()"
                        x-on:pointerdown="edgePointerDown($event)"
                    ></g>
                    <line x-show="ghostLine()" :x1="ghostLine()?.x1" :y1="ghostLine()?.y1" :x2="ghostLine()?.x2" :y2="ghostLine()?.y2" stroke="currentColor" stroke-width="2" stroke-dasharray="5 4" marker-end="url(#seta)" />
                </svg>

                <template x-for="node in nodes" :key="node.key">
                    <div
                        :data-node-key="node.key"
                        x-effect="node.label; editing; $nextTick(() => measure(node, $el))"
                        x-on:pointerdown="editing !== node.key && startNode($event, node)"
                        x-on:dblclick.stop="node.custom ? startRename(node) : removeNode(node)"
                        :style="`left: ${node.x}px; top: ${node.y}px`"
                        :class="node.custom ? 'bg-zinc-200 text-zinc-900' : 'bg-primary text-on-primary'"
                        class="group absolute flex cursor-move items-center gap-2 rounded-full px-4 py-1.5 text-sm whitespace-nowrap select-none"
                        x-bind:title="node.custom ? 'Duplo clique para renomear' : 'Duplo clique para tirar do mapa'"
                    >
                        <span x-show="editing !== node.key" x-text="node.label"></span>
                        <input
                            x-show="editing === node.key"
                            x-model="editText"
                            x-on:pointerdown.stop
                            x-on:keydown.enter.prevent="commitRename(node)"
                            x-on:keydown.escape.stop.prevent="editing = null"
                            x-on:blur="editing === node.key && commitRename(node)"
                            maxlength="60"
                            aria-label="Renomear conceito"
                            class="w-40 rounded bg-white px-1 text-zinc-900 outline-none"
                        />
                        <button
                            type="button"
                            x-show="node.custom && editing !== node.key"
                            x-on:pointerdown.stop
                            x-on:click.stop="removeNode(node)"
                            class="-mr-1 hidden size-4 cursor-pointer items-center justify-center rounded-full text-xs leading-none opacity-60 group-hover:flex hover:opacity-100"
                            aria-label="Remover conceito livre"
                        >&times;</button>
                        <span
                            x-on:pointerdown.stop="startEdge($event, node)"
                            class="size-4 cursor-crosshair rounded-full border-2 border-current opacity-60 hover:opacity-100"
                            :class="linking === node.key && 'bg-current opacity-100'"
                            title="Arraste até outro conceito, ou clique aqui e depois no destino"
                            aria-label="Arraste para ligar a outro conceito"
                        ></span>
                    </div>
                </template>
            </div>
        </div>

        <div x-data="mapTraining" class="border-surface-variant grid gap-4 border-t p-4 md:grid-cols-[1fr_16rem]">
            <div class="flex flex-col gap-2">
                <flux:text size="sm">Escreva o mapa de memória, em texto corrido.</flux:text>
                <textarea
                    x-ref="trainingText"
                    x-model="text"
                    :disabled="!running"
                    rows="8"
                    placeholder="Clique em Iniciar para começar a escrever."
                    aria-label="Mapa mental escrito"
                    class="bg-surface-container-lowest border-surface-variant w-full resize-y rounded-lg border p-3 text-sm outline-none focus:border-primary disabled:opacity-60"
                ></textarea>
                <div class="flex items-center justify-between gap-2">
                    <flux:text size="sm"><span x-text="words"></span> palavras</flux:text>
                    <flux:text size="sm" x-show="running || result"><span x-text="result ? result.found.length : foundLabels.length"></span>/<span x-text="mapped.length"></span> conceitos</flux:text>
                </div>
                <div class="flex flex-wrap gap-1" aria-live="polite">
                    <template x-for="label in (result ? result.found : foundLabels)" :key="label">
                        <span x-text="label" class="bg-primary/15 text-primary rounded-full px-3 py-1 text-sm"></span>
                    </template>
                </div>
            </div>

            <div class="bg-surface-container border-surface-variant flex flex-col items-center gap-3 rounded-lg border p-4">
                <div class="font-mono text-4xl tabular-nums" x-text="clock" role="timer"></div>
                <div class="flex gap-1" role="radiogroup" aria-label="Duração">
                    <template x-for="d in durations" :key="d">
                        <flux:button size="sm" x-on:click="pick(d)" x-bind:variant="minutes === d ? 'primary' : 'ghost'" x-bind:disabled="running || result">
                            <span x-text="d + ' min'"></span>
                        </flux:button>
                    </template>
                </div>
                <flux:button size="sm" variant="primary" class="w-full" x-show="!running && !result" x-bind:disabled="!mapped.length" x-on:click="start()">Iniciar</flux:button>
                <flux:button size="sm" variant="primary" class="w-full" x-show="running" x-on:click="submit()">Enviar</flux:button>
                <flux:button size="sm" class="w-full" x-show="result" x-on:click="reset()">Tentar de novo</flux:button>
                <flux:text size="sm" x-show="!mapped.length">Ponha conceitos no mapa para treinar.</flux:text>

                <div x-show="result" x-cloak class="w-full text-sm">
                    <div class="mb-1 text-lg font-semibold"><span x-text="result && Math.round(result.found.length / mapped.length * 100)"></span>%</div>
                    <div x-show="result && result.missing.length">
                        <flux:text size="sm">Faltaram:</flux:text>
                        <ul class="list-disc pl-4">
                            <template x-for="label in result ? result.missing : []" :key="label"><li x-text="label"></li></template>
                        </ul>
                    </div>
                    <flux:text size="sm" x-show="result && !result.missing.length">Todos os conceitos citados.</flux:text>
                </div>
            </div>
        </div>
    </div>
</div>
