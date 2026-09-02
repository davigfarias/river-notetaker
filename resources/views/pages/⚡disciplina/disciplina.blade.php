<div>
    <x-slot:headerActions>
        <flux:button variant="primary" icon="plus" href="{{ route('notas.criar') }}" wire:navigate> Nova Nota </flux:button>
    </x-slot:headerActions>

    <div class="-m-6 flex h-[calc(100vh-7rem)] overflow-hidden lg:-m-8">
        <div class="border-outline-variant bg-surface-container-lowest/30 flex min-h-0 w-full flex-col border-r md:w-1/3 lg:w-1/4">
            <div class="border-outline-variant shrink-0 border-b p-4">
                <div class="border-outline-variant/30 bg-surface-container primary mb-4 flex h-12 w-12 items-center justify-center rounded-lg border">
                    <flux:icon :name="$disciplineDTO->icon" class="size-6" />
                </div>
                <flux:heading size="lg">{{ $disciplineDTO->title }}</flux:heading>
                <flux:text class="mt-1">Histórico de Notas</flux:text>
            </div>

            <div class="border-outline-variant shrink-0 border-b p-3">
                <flux:input
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar notas..."
                    size="sm"
                />
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
            @forelse ($this->notes as $note)
                <button
                    type="button"
                    wire:key="note-{{ $note->id }}"
                    wire:click="selectNote({{ $note->id }})"
                    class="w-full border-b border-surface-variant p-4 text-left transition-colors hover:bg-surface-variant/50 {{ $this->selectedNote && $this->selectedNote->id === $note->id ? 'border-l-4 border-l-primary bg-surface-variant/30' : 'border-l-4 border-l-transparent' }}"
                >
                    <div class="mb-1 text-sm font-semibold {{ $this->selectedNote && $this->selectedNote->id === $note->id ? 'text-primary' : 'text-on-surface-variant' }}">
                        {{ $note->date() }}
                    </div>
                    <flux:heading size="base" class="mb-2 truncate">{{ $note->title }}</flux:heading>
                    <div class="flex flex-wrap gap-1">
                        @foreach ($note->tags ?? [] as $tag)
                            <flux:badge size="sm">#{{ $tag }}</flux:badge>
                        @endforeach
                    </div>
                </button>
            @empty
                <div class="p-4 text-center">
                    <flux:text size="sm">Nenhuma nota encontrada.</flux:text>
                </div>
            @endforelse

                <div class="mt-4 p-4 text-center">
                    <flux:text size="sm">Fim do histórico.</flux:text>
                </div>
            </div>
        </div>

        <div class="bg-surface hidden min-h-0 flex-1 flex-col overflow-y-auto p-8 md:flex lg:p-12">
            @if ($this->selectedNote)
                <div class="mx-auto w-full max-w-3xl pb-16">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <flux:heading
                                size="xl"
                                class="text-primary!"
                            >{{ $this->selectedNote->day() }}</flux:heading>
                            <flux:badge>{{ $this->selectedNote->year() }}</flux:badge>
                        </div>

                    </div>

                    <section class="group relative mb-4">
                        <flux:heading size="xl" level="1">{{ $this->selectedNote->title }}</flux:heading>

                        <button
                            type="button"
                            wire:click="edit('title')"
                            class="text-on-surface-variant hover:text-primary absolute top-0 right-0 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <flux:icon name="pencil" class="size-4" />
                        </button>
                    </section>

                    @island(name: 'summary', skip: true)
                        @placeholder
                            <div class="mb-6">
                                <flux:button
                                    size="sm"
                                    variant="subtle"
                                    icon="sparkles"
                                    wire:click="generateSummary"
                                    wire:island="summary"
                                >
                                    Gerar resumo com IA
                                </flux:button>
                            </div>
                        @endplaceholder

                        @if ($this->awaitingSummary)
                            <section
                                class="border-surface-variant bg-primary-container/10 mb-6 rounded-lg border p-4"
                                wire:poll.{{ config('summarizer.poll_interval', '2s') }}="pollCheckSummary"
                            >
                                <div class="mb-2 flex items-center gap-2">
                                    <flux:icon name="sparkles" class="text-primary size-4 animate-pulse" />
                                    <flux:heading size="xs">Resumo de IA</flux:heading>
                                </div>
                                <div class="space-y-2">
                                    <div class="bg-surface-variant h-3 w-full animate-pulse rounded"></div>
                                    <div class="bg-surface-variant h-3 w-3/4 animate-pulse rounded"></div>
                                </div>
                            </section>
                        @elseif ($this->selectedNote->ai_summary)
                            <section class="border-surface-variant bg-primary-container/10 mb-6 rounded-lg border p-4">
                                <div class="mb-2 flex items-center gap-2">
                                    <flux:icon name="sparkles" class="text-primary size-4" />
                                    <flux:heading size="xs">Resumo de IA</flux:heading>
                                    <flux:spacer />
                                    <flux:modal.trigger name="confirm-regenerate-summary">
                                        <flux:button size="sm" variant="subtle">Gerar novamente</flux:button>
                                    </flux:modal.trigger>
                                </div>
                                <flux:text class="text-sm">{{ $this->selectedNote->ai_summary }}</flux:text>
                            </section>
                        @else
                            <div class="mb-6">
                                <flux:button
                                    size="sm"
                                    variant="subtle"
                                    icon="sparkles"
                                    wire:click="generateSummary"
                                    wire:island="summary"
                                >
                                    Gerar resumo com IA
                                </flux:button>
                            </div>
                        @endif
                    @endisland

                    <flux:modal name="confirm-regenerate-summary" class="md:w-96">
                        <div class="space-y-6">
                            <div>
                                <flux:heading size="lg">Regenerar resumo?</flux:heading>
                                <flux:text class="mt-2">O resumo atual será substituído por um novo.</flux:text>
                            </div>
                            <div class="flex">
                                <flux:spacer />
                                <flux:modal.close>
                                    <flux:button variant="ghost">Cancelar</flux:button>
                                </flux:modal.close>
                                <flux:button variant="primary" wire:click="generateSummary" wire:island="summary">Regenerar</flux:button>
                            </div>
                        </div>
                    </flux:modal>

                    <section class="mb-8">
                        <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                            <flux:icon name="hashtag" class="text-primary size-5" />
                            <flux:heading size="sm">TAGS</flux:heading>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->allTags as $tag)
                                <button
                                    type="button"
                                    wire:click="toggleTag('{{ $tag->title }}')"
                                    class="rounded-full border px-3 py-1.5 text-sm transition-all
                                    {{ in_array($tag->title, $this->selectedNote->tags ?? [], true)
                                        ? 'border-primary bg-primary text-white'
                                        : 'border-surface-variant text-on-surface-variant hover:bg-surface-container-low' }}"
                                >
                                    {{ $tag->title }}
                                </button>
                            @endforeach
                        </div>
                    </section>

                    <div class="space-y-8">
                        <section class="relative">
                            <div class="group/header border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                <flux:icon name="light-bulb" class="text-primary size-5" />
                                <flux:heading size="sm">CONCEITOS</flux:heading>
                                <flux:spacer />
                                <button
                                    type="button"
                                    wire:click="$set('addingConcept', true)"
                                    class="text-on-surface-variant hover:text-primary opacity-0 transition-opacity group-hover/header:opacity-100"
                                >
                                    <flux:icon name="plus" class="size-4" />
                                </button>
                            </div>
                            <div class="space-y-3">
                                @foreach ($this->selectedNote->concepts ?? [] as $concept)
                                    <div class="group/item border-surface-variant bg-surface-container-lowest relative rounded-lg border p-3">
                                        <button
                                            type="button"
                                            wire:click="editConcept({{ $concept->id }})"
                                            class="text-on-surface-variant hover:text-primary absolute top-3 right-3 opacity-0 transition-opacity group-hover/item:opacity-100"
                                        >
                                            <flux:icon name="pencil" class="size-4" />
                                        </button>
                                        <div class="pr-6 font-semibold">{{ $concept->term }}</div>
                                        <flux:text class="mt-1">{{ $concept->definition }}</flux:text>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section class="relative">
                            <div class="group/header border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                <flux:icon name="hand-raised" class="text-secondary size-5" />
                                <flux:heading size="sm">CONSELHOS PASTORAIS</flux:heading>
                                <flux:spacer />
                                <button
                                    type="button"
                                    wire:click="$set('addingAdvice', true)"
                                    class="text-on-surface-variant hover:text-primary opacity-0 transition-opacity group-hover/header:opacity-100"
                                >
                                    <flux:icon name="plus" class="size-4" />
                                </button>
                            </div>
                            <div class="space-y-3">
                                @foreach($this->selectedNote->pastoral_advice ?? [] as $advice)
                                    <div class="group/item border-surface-variant bg-surface-container-lowest relative rounded-lg border p-3">
                                        <button
                                            type="button"
                                            wire:click="editAdvice({{ $advice->id }})"
                                            class="text-on-surface-variant hover:text-primary absolute top-3 right-3 opacity-0 transition-opacity group-hover/item:opacity-100"
                                        >
                                            <flux:icon name="pencil" class="size-4" />
                                        </button>
                                        <div class="pr-6 font-semibold">{{ $advice->category }}</div>
                                        <flux:text class="mt-1">{{ $advice->advice }}</flux:text>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        @php
                            $hasImpressions = filled($this->selectedNote->impressions);
                            $hasLifeExperiences = filled($this->selectedNote->life_experiences);
                            $bothPresent = $hasImpressions && $hasLifeExperiences;
                        @endphp

                        @if ($hasImpressions || $hasLifeExperiences)
                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                @if ($hasImpressions)
                                    <section class="group relative {{ $bothPresent ? '' : 'lg:col-span-2' }}">
                                        <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                            <flux:icon name="sparkles" class="text-tertiary size-5" />
                                            <flux:heading size="sm">IMPRESSÕES</flux:heading>
                                            <flux:spacer />
                                            <button
                                                type="button"
                                                wire:click="edit('impressions')"
                                                class="text-on-surface-variant hover:text-primary opacity-0 transition-opacity group-hover:opacity-100"
                                            >
                                                <flux:icon name="pencil" class="size-4" />
                                            </button>
                                        </div>
                                        <div class="prose dark:prose-invert">
                                            {!! Str::markdown($this->selectedNote->impressions) !!}
                                        </div>
                                    </section>
                                @endif

                                @if ($hasLifeExperiences)
                                    <section class="group relative {{ $bothPresent ? '' : 'lg:col-span-2' }}">
                                        <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                            <flux:icon name="book-open" class="text-on-surface-variant size-5" />
                                            <flux:heading size="sm">EXPERIÊNCIAS DE VIDA</flux:heading>
                                            <flux:spacer />
                                            <button
                                                type="button"
                                                wire:click="edit('life_experiences')"
                                                class="text-on-surface-variant hover:text-primary opacity-0 transition-opacity group-hover:opacity-100"
                                            >
                                                <flux:icon name="pencil" class="size-4" />
                                            </button>
                                        </div>
                                        <div class="prose dark:prose-invert">
                                            {!! Str::markdown($this->selectedNote->life_experiences) !!}
                                        </div>
                                    </section>
                                @endif
                            </div>
                        @endif

                        @if(filled($this->selectedNote->reference_materials))
                            <section>
                                <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                    <flux:icon name="book-open" class="text-on-surface-variant size-5" />
                                    <flux:heading size="sm">REFERÊNCIAS</flux:heading>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($this->selectedNote->reference_materials ?? [] as $reference)
                                        <a
                                            href="{{ route('referencias.show', $reference['id']) }}"
                                            wire:navigate
                                            wire:key="note-ref-{{ $reference['id'] }}"
                                            class="border-surface-variant bg-surface-container-lowest flex flex-col justify-between rounded-lg border p-3 shadow-sm transition-shadow hover:shadow-md h-full"
                                        >
                                            @php
                                                $iconEnum = \App\Enums\ReferencesIcon::tryFrom($reference['type'])
                                                            ?? \App\Enums\ReferencesIcon::BookOpen;
                                            @endphp

                                            <div class="mb-2">
                                                <flux:badge size="sm" icon="{{ $iconEnum->icon() }}" color="zinc">{{ $iconEnum->label() }}</flux:badge>
                                            </div>

                                            <div class="mt-auto">
                                                <flux:text class="text-xs font-medium leading-snug line-clamp-3" title="{{ $reference['title'] }}">
                                                    {{ $reference['title'] }}
                                                </flux:text>
                                                @if(! empty($reference['author']))
                                                    <flux:text class="text-[11px] text-on-surface-variant/80 mt-0.5">
                                                        {{ $reference['author'] }}{{ $reference['year'] ? ', '.$reference['year'] : '' }}
                                                    </flux:text>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>

                    <flux:modal name="edit-title" wire:model.self="editing.title" class="md:w-96">
                        <div class="space-y-6">
                            <flux:heading size="lg">Editar título</flux:heading>
                            <flux:text class="mt-2">Ao terminar sua edição, aperte "Salvar".</flux:text>
                            
                            <flux:input wire:model="draft.title" label="Título" />
                            <div class="flex">
                                <flux:spacer />
                                <flux:button variant="primary" wire:click="updateNote('title')">Salvar</flux:button>
                            </div>
                        </div>
                    </flux:modal>

                    <flux:modal name="edit-impressions" wire:model.self="editing.impressions" class="md:w-lg">
                        <div class="space-y-6">
                            <flux:heading size="lg">Editar impressões</flux:heading>
                            <flux:text class="mt-2">Ao terminar sua edição, aperte "Salvar".</flux:text>
                            @if ($editing['impressions'])
                                <div wire:ignore>
                                    <div x-data="markdownEditor($wire.entangle('draft.impressions'))">
                                        <textarea x-ref="textarea"></textarea>
                                    </div>
                                </div>
                            @endif
                            <div class="flex">
                                <flux:spacer />
                                <flux:button variant="primary" wire:click="updateNote('impressions')">Salvar</flux:button>
                            </div>
                        </div>
                    </flux:modal>

                    <flux:modal name="edit-life_experiences" wire:model.self="editing.life_experiences" class="md:w-lg">
                        <div class="space-y-6">
                            <flux:heading size="lg">Editar experiências de vida</flux:heading>
                            <flux:text class="mt-2">Ao terminar sua edição, aperte "Salvar".</flux:text>
                            @if ($editing['life_experiences'])
                                <div wire:ignore>
                                    <div x-data="markdownEditor($wire.entangle('draft.life_experiences'))">
                                        <textarea x-ref="textarea"></textarea>
                                    </div>
                                </div>
                            @endif
                            <div class="flex">
                                <flux:spacer />
                                <flux:button variant="primary" wire:click="updateNote('life_experiences')">Salvar</flux:button>
                            </div>
                        </div>
                    </flux:modal>

                    <flux:modal name="edit-concept" wire:model.self="editingConcept" class="md:w-96">
                        <div class="space-y-6">
                            <flux:heading size="lg">Editar conceito</flux:heading>
                            <flux:text class="mt-2">Ao terminar sua edição, aperte "Salvar".</flux:text>

                            <flux:input wire:model="editConceptForm.term" label="Termo" />
                            <flux:textarea wire:model="editConceptForm.definition" label="Definição" />
                            <div class="flex">
                                <flux:spacer />
                                <flux:button variant="primary" wire:click="updateConcept">Salvar</flux:button>
                            </div>
                        </div>
                    </flux:modal>

                    <flux:modal name="edit-advice" wire:model.self="editingAdvice" class="md:w-96">
                        <div class="space-y-6">
                            <flux:heading size="lg">Editar conselho pastoral</flux:heading>
                            <flux:text class="mt-2">Ao terminar sua edição, aperte "Salvar".</flux:text>

                            <flux:input wire:model="editAdviceForm.category" label="Categoria" />
                            <flux:textarea wire:model="editAdviceForm.advice" label="Conselho" />
                            <div class="flex">
                                <flux:spacer />
                                <flux:button variant="primary" wire:click="updateAdvice">Salvar</flux:button>
                            </div>
                        </div>
                    </flux:modal>

                    <flux:modal name="add-concept" wire:model.self="addingConcept" class="md:w-96">
                        <div class="space-y-6">
                            <flux:heading size="lg">Adicionar conceito</flux:heading>
                            <flux:text class="mt-2">Ao terminar, aperte "Adicionar".</flux:text>

                            <flux:input
                                wire:model="addConceptForm.term"
                                wire:input.debounce.500ms="verifyConceptExistence"
                                label="Termo"
                            />
                            <flux:textarea wire:model="addConceptForm.definition" label="Definição" />
                            <div class="flex">
                                <flux:spacer />
                                <flux:button variant="primary" wire:click="addConcept">Adicionar</flux:button>
                            </div>
                        </div>
                    </flux:modal>

                    <flux:modal name="add-advice" wire:model.self="addingAdvice" class="md:w-96">
                        <div class="space-y-6">
                            <flux:heading size="lg">Adicionar conselho pastoral</flux:heading>
                            <flux:text class="mt-2">Ao terminar, aperte "Adicionar".</flux:text>

                            <flux:input wire:model="addAdviceForm.category" label="Categoria" />
                            <flux:textarea wire:model="addAdviceForm.advice" label="Conselho" />
                            <div class="flex">
                                <flux:spacer />
                                <flux:button variant="primary" wire:click="addAdvice">Adicionar</flux:button>
                            </div>
                        </div>
                    </flux:modal>
                </div>
            @else
                <div class="flex flex-1 items-center justify-center">
                    <flux:text>Selecione uma nota para visualizar.</flux:text>
                </div>
            @endif
        </div>
    </div>
</div>
