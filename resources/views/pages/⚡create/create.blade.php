<div>
    <x-slot:headerActions>
        <flux:button variant="primary" icon="check" type="submit" form="form-nota">
            Salvar
        </flux:button>
    </x-slot:headerActions>

    <form id="form-nota" wire:submit="save" class="mx-auto max-w-[800px] space-y-8 pb-32">
        <div class="mt-4 space-y-4">
            <input
                type="text"
                wire:model="notes.title"
                placeholder="Título da Anotação..."
                class="text-on-surface w-full border-none bg-transparent p-0 text-4xl font-bold outline-none focus:ring-0"
            />
            <flux:error name="notes.title" />

            <div class="text-on-surface-variant flex flex-wrap items-center gap-4">
                <div class="border-surface-variant bg-surface-container flex items-center gap-1.5 rounded border px-2 py-1">
                    <flux:icon name="calendar" class="size-4" />
                    <flux:text size="sm">Hoje, {{ now()->format('H:i') }}</flux:text>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-64 shrink-0">
                        <flux:select wire:model="notes.discipline_id" wire:key="discipline-select">
                            <flux:select.option value="">Selecione uma disciplina</flux:select.option>
                            @foreach($this->disciplines as $discipline)
                                <flux:select.option value="{{ $discipline->id }}" wire:key="discipline-option-{{ $discipline->id }}">
                                    {{ $discipline->title }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:error name="notes.discipline_id" />
                    </div>
                </div>
            </div>
        </div>

        <section class="space-y-4">
            <section class="space-y-4">
                <div class="border-surface-variant flex items-center gap-2 border-b pb-2">
                    <flux:icon name="hashtag" class="text-primary size-5" />
                    <flux:heading size="sm">TAGS</flux:heading>
                </div>

                <div class="border-surface-variant bg-surface-container-lowest overflow-hidden rounded-xl border shadow-sm p-4 transition-colors duration-300">
                    <flux:text size="sm" class="mb-3 text-on-surface-variant">Selecione as tags que melhor descrevem esta anotação:</flux:text>

                    <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto pr-2">
                        @foreach ($this->tags as $tag)
                            <button
                                type="button"
                                wire:click="addTag('{{ $tag->title }}')"
                                class="rounded-full border px-3 py-1.5 text-sm transition-all focus:outline-none
                                {{ in_array($tag->title, $notes->tags ?? [])
                                ? 'border-primary bg-primary text-white'
                                : 'border-surface-variant text-on-surface-variant hover:bg-surface-container-low' }}"
                            >
                                {{ $tag->title }}
                            </button>
                        @endforeach
                    </div>

                    @error('notes.tags')
                    <span class="text-sm text-red-500 mt-2 block">{{ $message }}</span>
                    @enderror
                </div>
            </section>

            <div class="border-surface-variant flex items-center gap-2 border-b pb-2">
                <flux:icon name="light-bulb" class="text-primary size-5" />
                <flux:heading size="sm">CONCEITOS</flux:heading>
            </div>

            <div class="border-surface-variant bg-surface-container-lowest overflow-hidden rounded-xl border shadow-sm">
                @foreach ($notes->concepts ?? [] as $index => $concept)
                    <div
                        wire:key="concept-{{ $index }}"
                        class="border-surface-variant flex flex-col border-b sm:flex-row"
                    >
                        <div class="border-surface-variant bg-surface-container-low/50 border-b p-3 sm:w-1/3 sm:border-r sm:border-b-0">
                            <input
                                type="text"
                                wire:model="notes.concepts.{{ $index }}.term"
                                wire:input.debounce.500ms="verifyExistence({{ $index }})"
                                placeholder="Termo ou Palavra-chave"
                                class="text-on-surface w-full border-none bg-transparent p-0 outline-none focus:ring-0"
                            />
                        </div>
                        <div class="group relative bg-transparent p-3 sm:w-2/3">
                            <textarea
                                wire:model="notes.concepts.{{ $index }}.definition"
                                placeholder="Definição ou explicação do conceito..."
                                rows="2"
                                class="text-on-surface-variant w-full resize-none border-none bg-transparent p-0 outline-none focus:ring-0"
                            ></textarea>
                            @if (count($notes->concepts ?? []) > 1)
                                <button
                                    type="button"
                                    wire:click="removeConcept({{ $index }})"
                                    class="text-on-surface-variant hover:text-error absolute top-2 right-2 opacity-0 transition-all group-hover:opacity-100"
                                    title="Remover"
                                >
                                    <flux:icon name="x-mark" class="size-4" />
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach

                <button
                    type="button"
                    wire:click="addConcept"
                    class="bg-surface-container-low/30 text-primary-container hover:bg-surface-container-low flex w-full items-center justify-center gap-2 py-3 tracking-wider uppercase transition-colors"
                >
                    <flux:icon name="plus-circle" class="size-4" />
                    <flux:text size="sm" class="font-bold">Adicionar Conceito</flux:text>
                </button>
            </div>
        </section>

        <section class="space-y-4">
            <div class="border-surface-variant flex items-center gap-2 border-b pb-2">
                <flux:icon name="hand-raised" class="text-secondary size-5" />
                <flux:heading size="sm">CONSELHOS PASTORAIS</flux:heading>
            </div>

            <div class="border-surface-variant bg-surface-container-lowest overflow-hidden rounded-xl border shadow-sm">
                @foreach ($notes->pastoral_advice ?? [] as $index => $pastoral)
                    <div
                        wire:key="pastoral-{{ $index }}"
                        class="border-surface-variant flex flex-col border-b sm:flex-row"
                    >
                        <div class="border-surface-variant bg-surface-container-low/50 border-b p-3 sm:w-1/3 sm:border-r sm:border-b-0">
                            <input
                                type="text"
                                wire:model="notes.pastoral_advice.{{ $index }}.category"
                                placeholder="Categoria ou Tema"
                                class="text-on-surface w-full border-none bg-transparent p-0 outline-none focus:ring-0"
                            />
                        </div>
                        <div class="group relative bg-transparent p-3 sm:w-2/3">
                            <textarea
                                wire:model="notes.pastoral_advice.{{ $index }}.advice"
                                placeholder="Aplicações práticas, conselhos ou observações..."
                                rows="2"
                                class="text-on-surface-variant w-full resize-none border-none bg-transparent p-0 outline-none focus:ring-0"
                            ></textarea>
                            @if (count($notes->pastoral_advice ?? []) > 1)
                                <button
                                    type="button"
                                    wire:click="removePastoralAdvice({{ $index }})"
                                    class="text-on-surface-variant hover:text-error absolute top-2 right-2 opacity-0 transition-all group-hover:opacity-100"
                                    title="Remover"
                                >
                                    <flux:icon name="x-mark" class="size-4" />
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach

                <button
                    type="button"
                    wire:click="addPastoralAdvice"
                    class="bg-surface-container-low/30 text-secondary hover:bg-surface-container-low flex w-full items-center justify-center gap-2 py-3 tracking-wider uppercase transition-colors"
                >
                    <flux:icon name="plus-circle" class="size-4" />
                    <flux:text size="sm" class="font-bold">Adicionar Conselho</flux:text>
                </button>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-6">
            <section class="border-surface-variant bg-surface-container-lowest flex flex-col overflow-hidden rounded-xl border shadow-sm transition-colors duration-300">
                <div class="border-surface-variant bg-surface-container-low/80 flex items-center gap-2 border-b p-3">
                    <flux:icon name="sparkles" class="text-tertiary size-4" />
                    <flux:heading size="sm">IMPRESSÕES</flux:heading>
                </div>
                <div wire:ignore class="flex-1">
                    <div x-data="markdownEditor($wire.entangle('notes.impressions'))">
                        <textarea x-ref="textarea" placeholder="Suas impressões pessoais, dúvidas ou reflexões imediatas sobre o tema..."></textarea>
                    </div>
                </div>
            </section>

            <section class="border-surface-variant bg-surface-container-lowest flex flex-col overflow-hidden rounded-xl border shadow-sm transition-colors duration-300">
                <div class="border-surface-variant bg-surface-container-low/80 flex items-center gap-2 border-b p-3">
                    <flux:icon name="book-open" class="text-tertiary size-4" />
                    <flux:heading size="sm">EXPERIÊNCIAS DE VIDA</flux:heading>
                </div>
                <div wire:ignore class="flex-1">
                    <div x-data="markdownEditor($wire.entangle('notes.life_experiences'))">
                        <textarea x-ref="textarea" placeholder="Como este conteúdo se relaciona com vivências passadas ou observações cotidianas?"></textarea>
                    </div>
                </div>
            </section>
        </div>

        <section class="space-y-4">
            <div class="border-surface-variant flex items-center gap-2 border-b pb-2">
                <flux:icon name="book-open" class="text-on-surface-variant size-5" />
                <flux:heading size="sm">REFERÊNCIAS</flux:heading>
            </div>

            <div class="border-surface-variant bg-surface-container-lowest rounded-xl border p-4 shadow-sm space-y-4">

                @if ($this->linkedReferences->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->linkedReferences as $linked)
                            @php($linkedIcon = \App\Enums\ReferencesIcon::tryFrom($linked->type) ?? \App\Enums\ReferencesIcon::BookOpen)
                            <flux:badge wire:key="linked-{{ $linked->id }}" size="lg" icon="{{ $linkedIcon->icon() }}" color="zinc">
                                {{ $linked->title }}
                                <flux:badge.close wire:click="unlinkReference({{ $linked->id }})" />
                            </flux:badge>
                        @endforeach
                    </div>
                @endif

                <div class="relative">
                    <flux:input
                        wire:model.live.debounce.400ms="refSearch"
                        icon="magnifying-glass"
                        placeholder="Buscar obra na biblioteca..."
                        clearable
                    />

                    <div wire:loading.delay class="mt-2">
                        <flux:skeleton class="h-4 w-2/3" />
                    </div>

                    @if (filled($refSearch))
                        <div wire:loading.delay.remove class="mt-2 divide-y divide-surface-variant overflow-hidden rounded-lg border border-surface-variant">
                            @forelse ($this->referenceResults as $result)
                                @php($resultIcon = \App\Enums\ReferencesIcon::tryFrom($result->type) ?? \App\Enums\ReferencesIcon::BookOpen)
                                <button
                                    type="button"
                                    wire:key="result-{{ $result->id }}"
                                    wire:click="linkReference({{ $result->id }})"
                                    class="flex w-full items-center gap-3 bg-surface-container-lowest p-3 text-left hover:bg-surface-container-low"
                                >
                                    <flux:icon name="{{ $resultIcon->icon() }}" class="size-4 text-on-surface-variant shrink-0" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium">{{ $result->title }}</span>
                                        <span class="block truncate text-xs text-on-surface-variant">{{ $result->author }}{{ $result->year ? ', '.$result->year : '' }}</span>
                                    </span>
                                    <flux:icon name="plus" class="size-4 text-on-surface-variant shrink-0" />
                                </button>
                            @empty
                                <div class="bg-surface-container-lowest p-3 text-sm text-on-surface-variant">
                                    Nenhuma obra encontrada.
                                </div>
                            @endforelse
                        </div>
                    @endif
                </div>

                <flux:modal.trigger name="add-reference-material">
                    <flux:button size="sm" variant="ghost" icon="plus">Nova obra</flux:button>
                </flux:modal.trigger>
            </div>
        </section>
    </form>

    <flux:modal name="add-reference-material" class="md:w-[32rem]">
        <form wire:submit="addNewReference" class="space-y-5">
            <div>
                <flux:heading size="lg">Nova obra</flux:heading>
                <flux:text class="mt-2">Adiciona à biblioteca e vincula a esta nota.</flux:text>
            </div>

            <flux:input label="Título" wire:model="refForm.title" placeholder="Ex: A Vida Juntos" />
            <flux:input label="Autor" wire:model="refForm.author" placeholder="Ex: Dietrich Bonhoeffer" />

            @include('partials.reference-type-pills', ['model' => 'refForm.type', 'label' => 'Tipo'])

            <div class="flex gap-3">
                <flux:input label="Ano" type="number" wire:model="refForm.year" class="w-28" />
                <flux:input label="Editora" wire:model="refForm.publisher" placeholder="Opcional" class="flex-1" />
            </div>

            <flux:input label="URL" wire:model="refForm.url" placeholder="https:// (opcional)" />
            <flux:textarea label="Referência ABNT" wire:model="refForm.abnt_reference" rows="2" placeholder="Opcional" />

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Adicionar e vincular</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-concept" wire:model.self="editingConcept" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Editar conceito</flux:heading>
            </div>

            <flux:input
                label="Termo"
                wire:model="editConceptForm.term"
                placeholder="Ex: Graça" />
            <div>
                @error('editConceptForm.term') <span class="error">{{ $message }}</span> @enderror
            </div>
            <flux:textarea
                label="Definição"
                wire:model="editConceptForm.definition"
                placeholder="Favor imerecido..."
            />
            <div>
                @error('editConceptForm.definition') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="flex">
                <flux:spacer />
                <flux:button
                    type="button"
                    variant="primary"
                    wire:click="updateConcept">Salvar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
