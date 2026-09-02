<x-slot:headerActions>
    <flux:button variant="ghost" icon="arrow-left" href="{{ route('referencias') }}" wire:navigate>
        Biblioteca
    </flux:button>
</x-slot:headerActions>

@placeholder
    <x-slot:headerActions>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('referencias') }}" wire:navigate>
            Biblioteca
        </flux:button>
    </x-slot:headerActions>

    <div class="mx-auto w-full max-w-4xl py-8">
        <div class="space-y-4">
            <flux:skeleton class="h-5 w-24" />
            <flux:skeleton class="h-9 w-2/3" />
            <flux:skeleton class="h-4 w-1/2" />
            <flux:skeleton class="h-16 w-full" />
            <div class="pt-6 space-y-3">
                <flux:skeleton class="h-6 w-32" />
                <flux:skeleton class="h-24 w-full" />
                <flux:skeleton class="h-20 w-full" />
            </div>
        </div>
    </div>
@endplaceholder

@php($icon = \App\Enums\ReferencesIcon::tryFrom($this->material->type) ?? \App\Enums\ReferencesIcon::BookOpen)

<div class="mx-auto w-full max-w-4xl py-8">

        <div>
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <flux:badge size="sm" icon="{{ $icon->icon() }}" color="zinc">{{ $icon->label() }}</flux:badge>
                    <flux:heading size="xl" level="1" class="mt-3">{{ $this->material->title }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ $this->material->author }}{{ $this->material->year ? ' · '.$this->material->year : '' }}{{ $this->material->publisher ? ' · '.$this->material->publisher : '' }}
                    </flux:text>
                    @if ($this->material->url)
                        <flux:link href="{{ $this->material->url }}" target="_blank" class="mt-1 block text-sm">{{ $this->material->url }}</flux:link>
                    @endif
                </div>

                <div class="flex gap-2 shrink-0">
                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openEditMaterial">Editar</flux:button>
                    <flux:modal.trigger name="export">
                        <flux:button size="sm" variant="primary" icon="arrow-down-tray">Exportar</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-surface-variant bg-surface-container-low/50 p-3">
                <flux:text size="sm" class="text-on-surface-variant">
                    {{ app(\App\Support\Export\AbntFormatter::class)->reference($this->material) }}
                </flux:text>
            </div>

            <div class="mt-8 flex gap-1 overflow-x-auto whitespace-nowrap border-b border-surface-variant">
                <button type="button" wire:click="$set('activeTab', 'citacoes')"
                    @class([
                        'flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                        'border-primary text-primary' => $activeTab === 'citacoes',
                        'border-transparent text-on-surface-variant hover:text-on-surface' => $activeTab !== 'citacoes',
                    ])>
                    <flux:icon name="chat-bubble-bottom-center-text" class="size-4" />
                    Citações
                    <flux:badge size="sm">{{ $this->material->citations_count }}</flux:badge>
                </button>
                <button type="button" wire:click="$set('activeTab', 'perguntas')"
                    @class([
                        'flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                        'border-primary text-primary' => $activeTab === 'perguntas',
                        'border-transparent text-on-surface-variant hover:text-on-surface' => $activeTab !== 'perguntas',
                    ])>
                    <flux:icon name="academic-cap" class="size-4" />
                    Capítulos e Perguntas
                    <flux:badge size="sm">{{ $this->material->chapters->count() }}</flux:badge>
                </button>
            </div>

            <div class="mt-6" @if ($activeTab !== 'citacoes') hidden @endif>

            <flux:heading size="lg" level="2">
                Citações
                <flux:badge size="sm" class="ml-1">{{ $this->material->citations_count }}</flux:badge>
            </flux:heading>

            <form wire:submit="addCitation" class="mt-4 space-y-3 rounded-xl border border-surface-variant bg-surface-container-lowest p-4">
                <flux:textarea wire:model="citationForm.quote_text" rows="3" placeholder="Cole ou digite o trecho citado..." />
                <flux:error name="citationForm.quote_text" />
                <div class="flex flex-col sm:flex-row gap-3">
                    <flux:input wire:model="citationForm.location" placeholder="Localização (ex: p. 42, 01:12:30)" class="sm:max-w-64" />
                    <flux:input wire:model="citationForm.personal_note" placeholder="Nota pessoal (opcional)" />
                    <flux:spacer />
                    <flux:button type="submit" variant="primary" icon="plus">Adicionar citação</flux:button>
                </div>
            </form>

            <div wire:loading.delay.flex wire:target="addCitation,updateCitation,deleteCitation" class="hidden flex-col gap-3 mt-6">
                @for ($i = 0; $i < 3; $i++)
                    <div class="rounded-xl border border-surface-variant bg-surface-container-lowest p-4 space-y-2">
                        <flux:skeleton class="h-4 w-full" />
                        <flux:skeleton class="h-4 w-4/5" />
                    </div>
                @endfor
            </div>

            <div wire:loading.delay.remove wire:target="addCitation,updateCitation,deleteCitation" class="mt-6 space-y-3">
                @forelse ($this->material->citations as $citation)
                    <div wire:key="citation-{{ $citation->id }}" class="group rounded-xl border border-surface-variant bg-surface-container-lowest p-4">
                        <p class="italic text-on-surface-variant leading-relaxed">&ldquo;{{ $citation->quote_text }}&rdquo;</p>
                        <div class="mt-2 flex items-center gap-3">
                            @if ($citation->location)
                                <flux:text size="sm" class="text-on-surface-variant/80">{{ $citation->location }}</flux:text>
                            @endif
                            @if ($citation->personal_note)
                                <flux:text size="sm" class="text-on-surface-variant/80">— {{ $citation->personal_note }}</flux:text>
                            @endif
                            <flux:spacer />
                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <flux:button size="xs" variant="ghost" icon="pencil" wire:click="editCitation({{ $citation->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDeleteCitation({{ $citation->id }})" />
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-16 px-6 text-center rounded-xl border border-dashed border-surface-variant bg-surface-container-low">
                        <flux:icon name="chat-bubble-bottom-center-text" class="size-9 text-surface-variant-content/50 mb-3" />
                        <flux:text class="text-surface-variant-content">Nenhuma citação registrada para esta obra ainda.</flux:text>
                    </div>
                @endforelse
            </div>
            </div>{{-- /citacoes tab --}}

            <div class="mt-6" @if ($activeTab !== 'perguntas') hidden @endif>
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="lg" level="2">Capítulos</flux:heading>
                    <flux:button size="sm" variant="primary" icon="plus" wire:click="openCreateChapter">Novo capítulo</flux:button>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($this->material->chapters as $chapter)
                        <details wire:key="chapter-{{ $chapter->id }}" class="group rounded-xl border border-surface-variant bg-surface-container-lowest">
                            <summary class="flex cursor-pointer items-center gap-3 p-4">
                                <flux:icon name="chevron-right" class="size-4 shrink-0 transition-transform group-open:rotate-90" />
                                <span class="font-medium">{{ $chapter->title }}</span>
                                <flux:badge size="sm">{{ $chapter->questions->count() }}</flux:badge>
                                <flux:spacer />
                                <flux:button size="xs" variant="ghost" icon="pencil" wire:click.stop="editChapter({{ $chapter->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click.stop="confirmDeleteChapter({{ $chapter->id }})" />
                            </summary>

                            <div class="border-t border-surface-variant p-4 space-y-3">
                                <div class="flex flex-wrap gap-2">
                                    <flux:button size="xs" variant="primary" icon="play" href="{{ route('referencias.study', ['id' => $this->material->id, 'chapterId' => $chapter->id]) }}" wire:navigate>Estudar</flux:button>
                                    <flux:button size="xs" variant="ghost" icon="book-open" href="{{ route('referencias.study.review', ['id' => $this->material->id, 'chapterId' => $chapter->id]) }}" wire:navigate>Revisão</flux:button>
                                    <flux:button size="xs" variant="ghost" icon="chart-bar" href="{{ route('referencias.study.results', ['id' => $this->material->id, 'chapterId' => $chapter->id]) }}" wire:navigate>Resultados</flux:button>
                                    <flux:spacer />
                                    <flux:button size="xs" variant="ghost" icon="plus" wire:click="openCreateQuestion({{ $chapter->id }})">Pergunta</flux:button>
                                </div>

                                @forelse ($chapter->questions as $question)
                                    <div wire:key="question-{{ $question->id }}" class="group/q rounded-lg border border-surface-variant bg-surface-container-low p-3">
                                        <div class="flex items-start gap-2">
                                            <div class="flex-1">
                                                <p class="text-sm font-medium">{{ $question->prompt }}</p>
                                                <p class="mt-1 text-sm text-on-surface-variant line-clamp-2">{{ $question->reference_answer }}</p>
                                                @if ($question->is_cloze)
                                                    <flux:badge size="sm" color="purple" class="mt-2">Cloze</flux:badge>
                                                @endif
                                            </div>
                                            <div class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover/q:opacity-100">
                                                <flux:button size="xs" variant="ghost" icon="chevron-up" wire:click="moveQuestion({{ $question->id }}, {{ $loop->index - 1 }})" :disabled="$loop->first" />
                                                <flux:button size="xs" variant="ghost" icon="chevron-down" wire:click="moveQuestion({{ $question->id }}, {{ $loop->index + 1 }})" :disabled="$loop->last" />
                                                <flux:button size="xs" variant="ghost" icon="pencil" wire:click="editQuestion({{ $question->id }})" />
                                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDeleteQuestion({{ $question->id }})" />
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <flux:text size="sm" class="text-on-surface-variant">Nenhuma pergunta neste capítulo ainda.</flux:text>
                                @endforelse
                            </div>
                        </details>
                    @empty
                        <div class="flex flex-col items-center justify-center py-16 px-6 text-center rounded-xl border border-dashed border-surface-variant bg-surface-container-low">
                            <flux:icon name="academic-cap" class="size-9 text-surface-variant-content/50 mb-3" />
                            <flux:text class="text-surface-variant-content">Nenhum capítulo cadastrado para esta obra ainda.</flux:text>
                        </div>
                    @endforelse
                </div>
            </div>{{-- /perguntas tab --}}
        </div>

        <flux:modal name="edit-material" wire:model.self="editingMaterial" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-lg">
            <form wire:submit="updateMaterial" class="space-y-5">
                <flux:heading size="lg">Editar obra</flux:heading>

                <flux:input label="Título" wire:model="editForm.title" />
                <flux:input label="Autor" wire:model="editForm.author" />

                @include('partials.reference-type-pills', ['model' => 'editForm.type', 'label' => 'Tipo'])

                <div class="flex gap-3">
                    <flux:input label="Ano" type="number" wire:model="editForm.year" class="w-28" />
                    <flux:input label="Editora" wire:model="editForm.publisher" class="flex-1" />
                </div>
                <flux:input label="URL" wire:model="editForm.url" />
                <flux:textarea label="Referência ABNT" wire:model="editForm.abnt_reference" rows="2" />

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Salvar</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="edit-citation" wire:model.self="editingCitation" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-lg">
            <form wire:submit="updateCitation" class="space-y-4">
                <flux:heading size="lg">Editar citação</flux:heading>
                <flux:textarea label="Trecho" wire:model="editCitationForm.quote_text" rows="4" />
                <flux:input label="Localização" wire:model="editCitationForm.location" />
                <flux:textarea label="Nota pessoal" wire:model="editCitationForm.personal_note" rows="2" />
                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Salvar</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="delete-citation" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Remover citação</flux:heading>
                    <flux:text class="mt-2">Esta ação não pode ser desfeita.</flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" icon="trash" wire:click="deleteCitation">Remover</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="chapter-form" wire:model.self="creatingChapter" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <form wire:submit="addChapter" class="space-y-5">
                <flux:heading size="lg">Novo capítulo</flux:heading>
                <flux:input label="Título" wire:model="chapterForm.title" />
                <flux:error name="chapterForm.title" />
                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Criar</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="edit-chapter" wire:model.self="editingChapter" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <form wire:submit="updateChapter" class="space-y-5">
                <flux:heading size="lg">Editar capítulo</flux:heading>
                <flux:input label="Título" wire:model="editChapterForm.title" />
                <flux:error name="editChapterForm.title" />
                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Salvar</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="delete-chapter" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Remover capítulo</flux:heading>
                    <flux:text class="mt-2">Todas as perguntas e tentativas deste capítulo serão removidas. Esta ação não pode ser desfeita.</flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" icon="trash" wire:click="deleteChapter">Remover</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="question-form" wire:model.self="creatingQuestion" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-lg">
            <form wire:submit="addQuestion" class="space-y-4">
                <flux:heading size="lg">Nova pergunta</flux:heading>
                <flux:input label="Pergunta" wire:model="questionForm.prompt" />
                <flux:error name="questionForm.prompt" />
                <flux:textarea label="Resposta de referência" wire:model="questionForm.referenceAnswer" rows="4" />
                <flux:error name="questionForm.referenceAnswer" />
                <flux:input label="Palavras-chave (separadas por vírgula)" wire:model="questionForm.keywords" />
                <flux:checkbox label="Modo Cloze (completar lacunas)" wire:model="questionForm.isCloze" />
                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Criar</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="edit-question" wire:model.self="editingQuestion" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-lg">
            <form wire:submit="updateQuestion" class="space-y-4">
                <flux:heading size="lg">Editar pergunta</flux:heading>
                <flux:input label="Pergunta" wire:model="editQuestionForm.prompt" />
                <flux:error name="editQuestionForm.prompt" />
                <flux:textarea label="Resposta de referência" wire:model="editQuestionForm.referenceAnswer" rows="4" />
                <flux:error name="editQuestionForm.referenceAnswer" />
                <flux:input label="Palavras-chave (separadas por vírgula)" wire:model="editQuestionForm.keywords" />
                <flux:checkbox label="Modo Cloze (completar lacunas)" wire:model="editQuestionForm.isCloze" />
                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Salvar</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="delete-question" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Remover pergunta</flux:heading>
                    <flux:text class="mt-2">Esta ação não pode ser desfeita.</flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" icon="trash" wire:click="deleteQuestion">Remover</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="export" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <form wire:submit="export" class="space-y-5">
                <div>
                    <flux:heading size="lg">Exportar citações</flux:heading>
                    <flux:text class="mt-2">Gera um arquivo com todas as citações desta obra em formato ABNT. Fica pronto em "Exportações".</flux:text>
                </div>

                <flux:radio.group wire:model="exportFormat" label="Formato" variant="segmented">
                    <flux:radio value="docx" label="Word (.docx)" />
                    <flux:radio value="pdf" label="PDF (.pdf)" />
                </flux:radio.group>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary" icon="arrow-down-tray">Gerar</flux:button>
                </div>
            </form>
        </flux:modal>
</div>
