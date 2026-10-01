<div>
    <x-slot:headerActions>
        <flux:button variant="primary" icon="plus" href="{{ route('notas.criar', $disciplineSlug) }}" wire:navigate> Nova nota </flux:button>
    </x-slot:headerActions>

    <div class="-m-6 flex h-[calc(100vh-7rem)] overflow-hidden lg:-m-8">
        <div class="border-outline-variant bg-surface-container-lowest/30 min-h-0 w-full flex-col border-r @if ($this->mobileDetail) hidden @else flex @endif md:flex md:w-1/3 lg:w-1/4">
            <div class="border-outline-variant shrink-0 border-b p-4">
                <div class="border-outline-variant/30 bg-surface-container primary mb-4 flex h-12 w-12 items-center justify-center rounded-lg border">
                    <flux:icon :name="$disciplineDTO->icon" class="size-6" />
                </div>
                <flux:heading size="lg">{{ $disciplineDTO->title }}</flux:heading>
                <flux:text class="mt-1">Histórico de Notas</flux:text>

                @if ($this->allPrincipleTopics->isNotEmpty())
                    @php
                        $linkedTopics = $this->allPrincipleTopics->whereIn('id', $this->linkedTopicIds);
                        $availableTopics = $this->allPrincipleTopics->whereNotIn('id', $this->linkedTopicIds);
                    @endphp

                    <div class="mt-3 space-y-2">
                        <flux:text size="sm" class="text-on-surface-variant">Temas de princípios</flux:text>

                        @if ($linkedTopics->isNotEmpty())
                            <div class="flex flex-wrap gap-1">
                                @foreach ($linkedTopics as $topic)
                                    <flux:badge size="sm" wire:key="linked-topic-{{ $topic->id }}">
                                        {{ $topic->title }}
                                        <flux:badge.close wire:click="toggleTopic({{ $topic->id }})" />
                                    </flux:badge>
                                @endforeach
                            </div>
                        @endif

                        @if ($availableTopics->isNotEmpty())
                            <flux:select wire:model.live="topicToAdd" size="sm">
                                <flux:select.option value="">Adicionar tema...</flux:select.option>
                                @foreach ($availableTopics as $topic)
                                    <flux:select.option value="{{ $topic->id }}">{{ $topic->title }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:text size="sm" class="text-on-surface-variant">
                                Todos os temas já vinculados.
                                <a href="{{ route('principios') }}" wire:navigate class="text-primary hover:underline">Criar outro tema</a>
                            </flux:text>
                        @endif
                    </div>
                @endif
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

        <div class="bg-surface min-h-0 flex-1 flex-col overflow-y-auto p-6 md:flex lg:p-8 {{ $this->mobileDetail ? 'flex' : 'hidden' }}">
            @if ($this->selectedNote)
                <div class="mx-auto w-full max-w-6xl pb-16">
                    <div class="mb-4 md:hidden">
                        <flux:button variant="ghost" icon="arrow-left" wire:click="$set('mobileDetail', false)">Voltar</flux:button>
                    </div>
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <flux:heading
                                size="xl"
                                class="text-primary!"
                            >{{ $this->selectedNote->day() }}</flux:heading>
                            <flux:badge>{{ $this->selectedNote->year() }}</flux:badge>
                        </div>

                        <flux:button variant="ghost" icon="arrow-down-tray" href="{{ route('notas.exportar', $this->selectedNote->id) }}">
                            Exportar
                        </flux:button>
                    </div>

                    <section class="group relative mb-4">
                        @if ($editing['title'])
                            <div wire:key="edit-title-{{ $this->selectedNote->id }}" class="space-y-2">
                                <flux:input
                                    wire:model="draft.title"
                                    wire:keydown.enter="updateNote('title')"
                                    wire:keydown.escape="cancelEdit('title')"
                                    x-init="$el.focus()"
                                />
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="cancelEdit('title')">Cancelar</flux:button>
                                    <flux:button size="sm" variant="primary" wire:click="updateNote('title')">Salvar</flux:button>
                                </div>
                            </div>
                        @else
                            <flux:heading size="xl" level="1">{{ $this->selectedNote->title }}</flux:heading>

                            <button
                                type="button"
                                wire:click="edit('title')"
                                class="text-on-surface hover:text-primary absolute top-0 right-0 opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                <flux:icon name="pencil" class="size-4" />
                            </button>
                        @endif
                    </section>

                    {{-- O resumo é do aluno, não da IA: é ele que a revisão espaçada
                         cobra em lacunas, e sem ele a nota não entra na fila. --}}
                    <section class="group relative mb-6">
                        <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                            <flux:icon name="pencil-square" class="text-on-surface size-5" />
                            <flux:heading size="sm">RESUMO</flux:heading>
                            <flux:spacer />
                            <button
                                type="button"
                                wire:click="edit('summary')"
                                class="text-on-surface hover:text-primary opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                <flux:icon name="pencil" class="size-4" />
                            </button>
                        </div>

                        @if ($editing['summary'])
                            @include('partials.inline-markdown-editor', ['field' => 'summary', 'noteId' => $this->selectedNote->id])
                        @elseif (filled($this->selectedNote->summary))
                            @include('partials.note-field-links', [
                                'field' => 'summary',
                                'html' => $this->renderWithLinks($this->selectedNote->summary, $this->notePrincipleLinks->get('summary', collect())),
                                'links' => $this->notePrincipleLinks->get('summary', collect()),
                                'linkablePrinciples' => $this->linkablePrinciples,
                            ])
                        @else
                            <div class="border-outline-variant/60 flex flex-col items-start gap-2 rounded-lg border border-dashed p-4">
                                <flux:text size="sm" class="text-on-surface-variant">
                                    Sem resumo escrito. Esta nota fica fora da revisão até você escrever o seu.
                                </flux:text>
                                <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="edit('summary')">
                                    Escrever resumo
                                </flux:button>
                            </div>
                        @endif
                    </section>

                    <section class="mb-8">
                        <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                            <flux:icon name="hashtag" class="text-on-surface size-5" />
                            <flux:heading size="sm">TAGS</flux:heading>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->allTags as $tag)
                                <x-tag-toggle
                                    wire:click="toggleTag('{{ $tag->title }}')"
                                    :active="in_array($tag->title, $this->selectedNote->tags ?? [], true)"
                                >
                                    {{ $tag->title }}
                                </x-tag-toggle>
                            @endforeach
                        </div>
                    </section>

                    <div class="space-y-8">
                        <section class="relative">
                            <div class="group/header border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                <flux:icon name="light-bulb" class="text-on-surface size-5" />
                                <flux:heading size="sm">CONCEITOS</flux:heading>
                                <flux:spacer />
                                <button
                                    type="button"
                                    wire:click="$set('addingConcept', true)"
                                    class="text-on-surface hover:text-primary opacity-0 transition-opacity group-hover/header:opacity-100"
                                >
                                    <flux:icon name="plus" class="size-4" />
                                </button>
                            </div>
                            <div class="space-y-3">
                                @if ($addingConcept)
                                    <div wire:key="addingConcept-form" class="border-primary/40 bg-surface-container-lowest space-y-3 rounded-lg border p-3" x-on:keydown.escape="$wire.set('addingConcept', false)">
                                        <flux:input
                                            wire:model="addConceptForm.term"
                                            wire:input.debounce.500ms="verifyConceptExistence"
                                            label="Termo"
                                            x-init="$el.focus()"
                                        />
                                        <flux:textarea wire:model="addConceptForm.definition" label="Definição" />
                                        <div class="flex justify-end gap-2">
                                            <flux:button size="sm" variant="ghost" wire:click="$set('addingConcept', false)">Cancelar</flux:button>
                                            <flux:button size="sm" variant="primary" wire:click="addConcept">Adicionar</flux:button>
                                        </div>
                                    </div>
                                @endif

                                @foreach ($this->selectedNote->concepts ?? [] as $concept)
                                    @if ($editingConceptId === $concept->id)
                                        <div wire:key="edit-concept-{{ $concept->id }}" class="border-primary/40 bg-surface-container-lowest space-y-3 rounded-lg border p-3" x-on:keydown.escape="$wire.set('editingConceptId', null)">
                                            <flux:input wire:model="editConceptForm.term" label="Termo" />
                                            <flux:textarea wire:model="editConceptForm.definition" label="Definição" />
                                            <div class="flex justify-end gap-2">
                                                <flux:button size="sm" variant="ghost" wire:click="$set('editingConceptId', null)">Cancelar</flux:button>
                                                <flux:button size="sm" variant="primary" wire:click="updateConcept">Salvar</flux:button>
                                            </div>
                                        </div>
                                    @else
                                        <div wire:key="concept-{{ $concept->id }}" class="group/item border-surface-variant bg-surface-container-lowest relative rounded-lg border p-3">
                                            <button
                                                type="button"
                                                wire:click="editConcept({{ $concept->id }})"
                                                class="text-on-surface hover:text-primary absolute top-3 right-3 opacity-0 transition-opacity group-hover/item:opacity-100"
                                            >
                                                <flux:icon name="pencil" class="size-4" />
                                            </button>
                                            <div class="pr-6 font-semibold">{{ $concept->term }}</div>
                                            <flux:text class="mt-1">{{ $concept->definition }}</flux:text>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </section>

                        <section class="relative">
                            <div class="group/header border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                <flux:icon name="hand-raised" class="text-on-surface size-5" />
                                <flux:heading size="sm">CONSELHOS PASTORAIS</flux:heading>
                                <flux:spacer />
                                <button
                                    type="button"
                                    wire:click="$set('addingAdvice', true)"
                                    class="text-on-surface hover:text-primary opacity-0 transition-opacity group-hover/header:opacity-100"
                                >
                                    <flux:icon name="plus" class="size-4" />
                                </button>
                            </div>
                            <div class="space-y-3">
                                @if ($addingAdvice)
                                    <div wire:key="addingAdvice-form" class="border-primary/40 bg-surface-container-lowest space-y-3 rounded-lg border p-3" x-on:keydown.escape="$wire.set('addingAdvice', false)">
                                        <flux:input wire:model="addAdviceForm.category" label="Categoria" x-init="$el.focus()" />
                                        <flux:textarea wire:model="addAdviceForm.advice" label="Conselho" />
                                        <div class="flex justify-end gap-2">
                                            <flux:button size="sm" variant="ghost" wire:click="$set('addingAdvice', false)">Cancelar</flux:button>
                                            <flux:button size="sm" variant="primary" wire:click="addAdvice">Adicionar</flux:button>
                                        </div>
                                    </div>
                                @endif

                                @foreach ($this->selectedNote->pastoral_advice ?? [] as $advice)
                                    @if ($editingAdviceId === $advice->id)
                                        <div wire:key="edit-advice-{{ $advice->id }}" class="border-primary/40 bg-surface-container-lowest space-y-3 rounded-lg border p-3" x-on:keydown.escape="$wire.set('editingAdviceId', null)">
                                            <flux:input wire:model="editAdviceForm.category" label="Categoria" />
                                            <flux:textarea wire:model="editAdviceForm.advice" label="Conselho" />
                                            <div class="flex justify-end gap-2">
                                                <flux:button size="sm" variant="ghost" wire:click="$set('editingAdviceId', null)">Cancelar</flux:button>
                                                <flux:button size="sm" variant="primary" wire:click="updateAdvice">Salvar</flux:button>
                                            </div>
                                        </div>
                                    @else
                                        <div wire:key="advice-{{ $advice->id }}" class="group/item border-surface-variant bg-surface-container-lowest relative rounded-lg border p-3">
                                            <button
                                                type="button"
                                                wire:click="editAdvice({{ $advice->id }})"
                                                class="text-on-surface hover:text-primary absolute top-3 right-3 opacity-0 transition-opacity group-hover/item:opacity-100"
                                            >
                                                <flux:icon name="pencil" class="size-4" />
                                            </button>
                                            <div class="pr-6 font-semibold">{{ $advice->category }}</div>
                                            <flux:text class="mt-1">{{ $advice->advice }}</flux:text>
                                        </div>
                                    @endif
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
                                            <flux:icon name="sparkles" class="text-on-surface size-5" />
                                            <flux:heading size="sm">IMPRESSÕES</flux:heading>
                                            <flux:spacer />
                                            <button
                                                type="button"
                                                wire:click="edit('impressions')"
                                                class="text-on-surface hover:text-primary opacity-0 transition-opacity group-hover:opacity-100"
                                            >
                                                <flux:icon name="pencil" class="size-4" />
                                            </button>
                                        </div>
                                        @if ($editing['impressions'])
                                            @include('partials.inline-markdown-editor', ['field' => 'impressions', 'noteId' => $this->selectedNote->id])
                                        @else
                                            @include('partials.note-field-links', [
                                                'field' => 'impressions',
                                                'html' => $this->renderWithLinks($this->selectedNote->impressions, $this->notePrincipleLinks->get('impressions', collect())),
                                                'links' => $this->notePrincipleLinks->get('impressions', collect()),
                                                'linkablePrinciples' => $this->linkablePrinciples,
                                            ])
                                        @endif
                                    </section>
                                @endif

                                @if ($hasLifeExperiences)
                                    <section class="group relative {{ $bothPresent ? '' : 'lg:col-span-2' }}">
                                        <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                            <flux:icon name="book-open" class="text-on-surface size-5" />
                                            <flux:heading size="sm">EXPERIÊNCIAS DE VIDA</flux:heading>
                                            <flux:spacer />
                                            <button
                                                type="button"
                                                wire:click="edit('life_experiences')"
                                                class="text-on-surface hover:text-primary opacity-0 transition-opacity group-hover:opacity-100"
                                            >
                                                <flux:icon name="pencil" class="size-4" />
                                            </button>
                                        </div>
                                        @if ($editing['life_experiences'])
                                            @include('partials.inline-markdown-editor', ['field' => 'life_experiences', 'noteId' => $this->selectedNote->id])
                                        @else
                                            @include('partials.note-field-links', [
                                                'field' => 'life_experiences',
                                                'html' => $this->renderWithLinks($this->selectedNote->life_experiences, $this->notePrincipleLinks->get('life_experiences', collect())),
                                                'links' => $this->notePrincipleLinks->get('life_experiences', collect()),
                                                'linkablePrinciples' => $this->linkablePrinciples,
                                            ])
                                        @endif
                                    </section>
                                @endif
                            </div>
                        @endif

                        @if(filled($this->selectedNote->reference_materials))
                            <section>
                                <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                    <flux:icon name="book-open" class="text-on-surface size-5" />
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

                    <flux:modal name="link-principle" variant="flyout" position="right" class="w-full max-w-[calc(100vw-2rem)] md:max-w-lg">
                        <div class="space-y-4">
                            <flux:heading size="lg">Princípios</flux:heading>

                            @if (filled($pendingSnippet))
                                <flux:text class="text-sm text-on-surface-variant italic">Linkar: "{{ $pendingSnippet }}"</flux:text>
                            @endif

                            <flux:input
                                wire:model.live.debounce.300ms="principleSearch"
                                icon="magnifying-glass"
                                placeholder="Buscar princípio..."
                                clearable
                            />

                            <div class="divide-y divide-surface-variant overflow-y-auto rounded-lg border border-surface-variant">
                                @forelse ($this->filteredLinkablePrinciples as $principle)
                                    <details wire:key="linkable-principle-{{ $principle->id }}" class="group">
                                        <summary class="flex cursor-pointer list-none items-center gap-2 p-3 text-sm hover:bg-surface-container-low">
                                            <flux:icon name="chevron-right" variant="micro" class="transition-transform group-open:rotate-90" />
                                            <flux:badge size="sm">{{ $principle->principleTopic->title }}</flux:badge>
                                            {{ $principle->title ?? $principle->concept->term }}
                                        </summary>

                                        <div class="space-y-3 px-3 pb-3">
                                            @if ($principle->type === \App\Enums\PrincipleType::Concept)
                                                <p class="text-sm leading-relaxed text-on-surface-variant whitespace-pre-wrap">{{ $principle->concept->definition }}</p>
                                            @else
                                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                                    {!! Str::markdownRich($principle->body) !!}
                                                </div>
                                            @endif

                                            @if (filled($pendingSnippet))
                                                <flux:button size="xs" variant="primary" wire:click="linkPendingPrinciple({{ $principle->id }})">
                                                    Linkar ao trecho
                                                </flux:button>
                                            @endif
                                        </div>
                                    </details>
                                @empty
                                    <div class="p-3 text-sm text-on-surface-variant">Nenhum princípio encontrado.</div>
                                @endforelse
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
