<x-slot:headerActions>
    <flux:button variant="primary" icon="plus" href="{{ route('notas.criar') }}" wire:navigate>
        Nova Nota
    </flux:button>
</x-slot:headerActions>

@placeholder
    <div>
        <div class="mx-auto w-full max-w-7xl py-8">

            <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <flux:heading size="xl" level="1">Dicionário de Conceitos</flux:heading>
                    <flux:text class="mt-2">Visão geral e glossário de conceitos.</flux:text>
                </div>
            </div>

            <div class="mb-8 flex flex-wrap gap-1 md:gap-2">
                <flux:skeleton.line animate="shimmer" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach (range(1, 5) as $i)
                    <div class="group border-surface-variant bg-surface-container-low hover:bg-surface-variant/40 relative flex h-48 flex-col justify-between overflow-hidden rounded-xl border p-6 shadow-sm transition-colors hover:shadow-md">

                        <div class="primary bg-primary-container/10 absolute -top-4 -right-4 h-24 w-24 rounded-bl-full transition-transform group-hover:scale-110"></div>

                        <div class="absolute top-2 right-2 z-10">
                            <flux:skeleton class="size-5 rounded-full" />
                        </div>
                        <flux:skeleton class="size-10 rounded-full" />
                        <flux:skeleton class="h-5 w-3/4" />
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endplaceholder

<div>
    <div class="mx-auto w-full max-w-7xl py-8">

        <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">Dicionário de Conceitos</flux:heading>
                <flux:text class="mt-2">Visão geral e glossário de conceitos.</flux:text>
            </div>

            <div>
                <flux:modal.trigger name="add-concept">
                    <flux:button>Adicionar um novo conceito</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <div class="mb-8">
            <flux:input
                icon="magnifying-glass"
                wire:model.live.debounce.500ms="search"
                placeholder="Pesquisar conceito por título..."
                clearable
                class="max-w-md"
            />
        </div>

        <!-- Barra de Navegação Alfabética (FluxUI) -->
        <div class="mb-8 flex flex-wrap gap-1 md:gap-2">
            @foreach($this->alphabet as $letter)
                <flux:button
                    wire:click="selectLetter('{{ $letter }}')"
                    size="sm"
                    variant="{{ $selectedLetter === $letter ? 'primary' : 'subtle' }}"
                    class="w-10 h-10 flex items-center justify-center px-0!"
                >
                    {{ $letter }}
                </flux:button>
            @endforeach
        </div>

        <!-- Indicador de Status -->
        <div class="mb-4">
            @if($selectedLetter)
                <flux:heading size="lg">Conceitos com a letra "{{ $selectedLetter }}"</flux:heading>
            @elseif(filled($search))
                <flux:heading size="lg">Resultados</flux:heading>
            @else
                <flux:heading size="lg">Últimos adicionados</flux:heading>
            @endif
        </div>

        <!-- Lista/Empty State -->
        @if($this->concepts->isEmpty())
            <div class="flex flex-col items-center justify-center py-24 px-6 text-center rounded-xl border border-surface-variant bg-surface-container-low border-dashed">
                <flux:icon name="document-magnifying-glass" class="size-10 text-surface-variant-content/50 mb-3" />
                <flux:heading size="md">Nenhum conceito encontrado</flux:heading>
                <flux:text class="mt-2 text-surface-variant-content">
                    Ainda não existem conceitos
                    @if($selectedLetter)
                        iniciados com a letra "{{ $selectedLetter }}"
                    @else
                        cadastrados
                    @endif.
                </flux:text>
            </div>
        @else
            <!-- Ajustado para grid responsivo -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($this->concepts as $concept)
                    @php
                        $charLimit = 85;
                        $isLong = mb_strlen($concept->definition) > $charLimit;
                    @endphp

                        <!-- Trocado h-48 por min-h-56 para caber a fonte grande e o botão -->
                    <div
                        wire:key="concept-{{ $concept->id }}"
                        class="group border-surface-variant bg-surface-container-low hover:bg-surface-variant/40 relative flex min-h-56 flex-col overflow-hidden rounded-xl border p-6 shadow-sm transition-colors hover:shadow-md"
                    >
                        <!-- Efeito de fundo no hover -->
                        <div class="primary bg-primary-container/10 absolute -top-4 -right-4 h-24 w-24 rounded-bl-full transition-transform group-hover:scale-110 pointer-events-none"></div>

                        <button
                            type="button"
                            wire:click="edit({{ $concept->id }})"
                            class="text-on-surface-variant hover:text-primary absolute top-4 right-4 z-10 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <flux:icon name="pencil" class="size-4" />
                        </button>

                        <!-- Título do Conceito -->
                        <div class="relative z-10 mb-3">
                            <flux:heading size="lg" class="group-hover:text-primary transition-colors">
                                {{ $concept->term }}
                            </flux:heading>
                        </div>

                        <!-- Definição (Sem ícone, fonte grande) -->
                        <div class="relative z-10 flex flex-col flex-1">
                            <p class="text-lg md:text-xl text-on-surface-variant font-medium leading-snug">
                                {{ \Illuminate\Support\Str::limit($concept->definition, $charLimit) }}
                            </p>

                            <!-- Acionador da Modal (Fica no card, a modal fica fora) -->
                            @if($isLong)
                                <div class="mt-auto pt-4">
                                    <flux:modal.trigger name="modal-concept-{{ $concept->id }}">
                                        <button type="button" class="text-sm font-semibold text-primary hover:underline cursor-pointer">
                                            Ver completo
                                        </button>
                                    </flux:modal.trigger>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @foreach ($this->concepts as $concept)
                @if(mb_strlen($concept->definition) > 85)
                    <flux:modal wire:key="modal-concept-{{ $concept->id }}" name="modal-concept-{{ $concept->id }}" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-lg space-y-6">
                        <div>
                            <flux:heading size="xl" class="mb-4">{{ $concept->term }}</flux:heading>

                            <p class="text-lg text-on-surface leading-relaxed">
                                {{ $concept->definition }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <flux:modal.close>
                                <flux:button variant="ghost">Fechar</flux:button>
                            </flux:modal.close>
                            <flux:button variant="primary" wire:click="edit({{ $concept->id }})">Editar</flux:button>
                        </div>
                    </flux:modal>
                @endif
            @endforeach
        @endif

        <flux:modal name="add-concept" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-md">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Adicionar um novo conceito</flux:heading>
                    <flux:text class="mt-2">Precisa de um conceito sem precisar de uma nota? Adicione diretamente aqui.</flux:text>
                </div>

                {{-- O <button> de IA ganha data-loading automaticamente durante a
                     chamada síncrona; o grupo reage a isso sem wire:target. --}}
                <div class="group space-y-4">
                    <div>
                        <div class="flex items-end gap-2">
                            <flux:input
                                label="Termo"
                                wire:model="formConcept.term"
                                placeholder="Ex: Graça"
                                class="flex-1"
                            />
                            <flux:button
                                icon="sparkles"
                                variant="filled"
                                wire:click="generateDefinition"
                                wire:bind:disabled="!$wire.formConcept.term || $wire.formConcept.term.trim().length < 5"
                            >
                                Definir com IA
                            </flux:button>
                        </div>
                        <flux:error name="formConcept.term" />
                    </div>

                    {{-- Skeleton visível apenas enquanto a IA gera --}}
                    <div class="hidden flex-col gap-3 group-has-data-loading:flex">
                        <flux:skeleton class="h-4 w-40" />
                        <flux:skeleton class="h-16 w-full" />
                        <flux:skeleton class="h-16 w-full" />
                    </div>

                    {{-- Definições geradas pela IA (ocultas durante a geração) --}}
                    @if ($aiDefinitions)
                        <div class="space-y-2 group-has-data-loading:hidden">
                            <div class="flex items-center justify-between">
                                <flux:text size="sm" class="font-medium">Escolha uma definição</flux:text>
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    icon="pencil-square"
                                    wire:click="clearAiDefinitions"
                                >
                                    Escrever manualmente
                                </flux:button>
                            </div>

                            <flux:radio.group wire:model.live="selectedDefinition" variant="cards" class="flex-col">
                                <flux:radio value="definition_a" label="Definição A" description="{{ $aiDefinitions['definition_a'] }}" />
                                <flux:radio value="definition_b" label="Definição B" description="{{ $aiDefinitions['definition_b'] }}" />
                            </flux:radio.group>
                        </div>
                    @endif
                </div>

                {{-- Campo Definição (textarea editável) --}}
                <flux:field>
                    <flux:label>Definição</flux:label>
                    <flux:textarea
                        wire:model="formConcept.definition"
                        rows="4"
                        placeholder="Favor imerecido..."
                    />
                    <flux:error name="formConcept.definition" />
                </flux:field>

                <div class="flex">
                    <flux:spacer />
                    <flux:button
                        type="submit"
                        variant="primary"
                        wire:click="addSoleConcept">Adicionar Conceito</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="edit-concept" wire:model.self="editingConcept" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Editar conceito</flux:heading>
                </div>

                <flux:input
                    label="Termo"
                    wire:model='editConceptForm.term'
                    placeholder="Ex: Graça" />
                <div>
                    @error('editConceptForm.term') <span class="error">{{ $message }}</span> @enderror
                </div>
                <flux:textarea
                    label="Definição"
                    wire:model='editConceptForm.definition'
                    placeholder="Favor imerecido..."
                />
                <div>
                    @error('editConceptForm.definition') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="flex">
                    <flux:spacer />
                    <flux:button
                        type="submit"
                        variant="primary"
                        wire:click="updateConcept">Salvar</flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</div>
