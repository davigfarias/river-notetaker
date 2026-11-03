<x-slot:headerActions>
    <flux:button variant="ghost" icon="arrow-down-tray" href="{{ route('referencias.exportacoes') }}" wire:navigate>
        <span class="hidden sm:inline">Exportações</span>
    </flux:button>
</x-slot:headerActions>

@placeholder
    <x-slot:headerActions>
        <flux:button variant="ghost" icon="arrow-down-tray" href="{{ route('referencias.exportacoes') }}" wire:navigate>
            <span class="hidden sm:inline">Exportações</span>
        </flux:button>
    </x-slot:headerActions>

    <div class="w-full py-8">
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">Materiais de Referência</flux:heading>
                <flux:text class="mt-2">Sua biblioteca de livros, artigos e outras obras &mdash; com as citações que você coleciona.</flux:text>
            </div>

            <flux:button icon="plus">Nova obra</flux:button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @for ($i = 0; $i < 6; $i++)
                <div class="rounded-xl border border-surface-variant bg-surface-container-lowest p-4 space-y-3">
                    <flux:skeleton class="h-4 w-20" />
                    <flux:skeleton class="h-5 w-3/4" />
                    <flux:skeleton class="h-4 w-1/2" />
                </div>
            @endfor
        </div>
    </div>
@endplaceholder

<div class="w-full py-8">

    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Materiais de Referência</flux:heading>
            <flux:text class="mt-2">Sua biblioteca de livros, artigos e outras obras &mdash; com as citações que você coleciona.</flux:text>
        </div>

        <flux:modal.trigger name="add-material">
            <flux:button icon="plus">Nova obra</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="mb-6 flex flex-col gap-3">
        <div class="flex flex-col sm:flex-row gap-3">
            <flux:input
                icon="magnifying-glass"
                wire:model.live.debounce.400ms="filter"
                placeholder="Filtrar por título ou autor..."
                clearable
                class="max-w-md"
            />
            <flux:spacer />
            <flux:button variant="ghost" icon="magnifying-glass-circle" href="{{ route('referencias.busca') }}" wire:navigate>
                Buscar nas citações
            </flux:button>
        </div>

        @include('partials.reference-type-pills', ['model' => 'type', 'live' => true, 'includeAll' => true])

        <div class="flex flex-wrap items-center gap-2">
            @if ($this->groups->isNotEmpty())
                <flux:radio.group wire:model.live="group" variant="pills" class="flex flex-wrap gap-2">
                    <flux:radio value="">Todos os grupos</flux:radio>
                    @foreach ($this->groups as $referenceGroup)
                        <flux:radio wire:key="group-filter-{{ $referenceGroup->id }}" value="{{ $referenceGroup->id }}">{{ $referenceGroup->name }}</flux:radio>
                    @endforeach
                </flux:radio.group>
            @else
                <flux:text size="sm">Nenhum grupo ainda. Crie um para juntar várias obras num espaço só.</flux:text>
            @endif

            <flux:modal.trigger name="add-group">
                <flux:button size="sm" variant="ghost" icon="folder-plus">Novo grupo</flux:button>
            </flux:modal.trigger>

            @if ($this->activeGroup)
                <flux:modal.trigger name="delete-group">
                    <flux:button size="sm" variant="ghost" icon="trash">Apagar grupo</flux:button>
                </flux:modal.trigger>
            @endif
        </div>
    </div>

    <div wire:loading.delay.flex wire:target="filter,type,group,previousPage,nextPage,gotoPage" class="hidden flex-wrap gap-4">
        @for ($i = 0; $i < 6; $i++)
            <div class="w-full sm:w-[calc(50%-0.5rem)] lg:w-[calc(33.333%-0.75rem)] rounded-xl border border-surface-variant bg-surface-container-lowest p-4 space-y-3">
                <flux:skeleton class="h-4 w-20" />
                <flux:skeleton class="h-5 w-3/4" />
                <flux:skeleton class="h-4 w-1/2" />
            </div>
        @endfor
    </div>

    <div wire:loading.delay.remove wire:target="filter,type,group,previousPage,nextPage,gotoPage">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($this->materials as $material)
                @php($icon = \App\Enums\ReferencesIcon::tryFrom($material->type) ?? \App\Enums\ReferencesIcon::BookOpen)
                <div
                    wire:key="material-{{ $material->id }}"
                    class="group relative flex flex-col justify-between rounded-xl border border-surface-variant bg-surface-container-lowest p-4 shadow-sm transition-shadow hover:shadow-md"
                >
                    {{-- Link esticado: o cartão inteiro abre a obra, e o seletor de grupos fica fora do <a>. --}}
                    <a href="{{ route('referencias.show', $material->id) }}" wire:navigate class="absolute inset-0 rounded-xl" aria-label="Abrir {{ $material->title }}"></a>
                    <div>
                        <flux:badge size="sm" icon="{{ $icon->icon() }}" color="zinc">{{ $icon->label() }}</flux:badge>
                        <flux:heading class="mt-3 leading-snug group-hover:text-primary">{{ $material->title }}</flux:heading>
                        @if ($material->author)
                            <flux:text size="sm" class="mt-1">{{ $material->author }}{{ $material->year ? ', '.$material->year : '' }}</flux:text>
                        @endif
                    </div>
                    <div class="mt-4 space-y-2">
                        @if ($material->hasReadingProgress())
                            <div>
                                <div class="h-1.5 rounded-full bg-surface-variant">
                                    <div class="h-1.5 rounded-full bg-primary" style="width: {{ $material->readingProgressPercent() }}%"></div>
                                </div>
                                <flux:text size="xs" class="mt-1 text-on-surface-variant">
                                    {{ $material->pagesRead() }}/{{ $material->pagesTotal() }} págs
                                    @if ($material->reading_status)
                                        &middot; {{ $material->reading_status->label() }}
                                    @endif
                                </flux:text>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-2">
                            <flux:text size="sm" class="text-on-surface-variant">
                                {{ $material->citations_count }} {{ $material->citations_count === 1 ? 'citação' : 'citações' }}
                            </flux:text>

                            <flux:dropdown position="bottom" align="end" class="relative z-1">
                                <flux:button size="xs" variant="ghost" icon="folder" aria-label="Escolher grupos de {{ $material->title }}">
                                    Grupos
                                    @if ($material->groups->isNotEmpty())
                                        <flux:badge size="sm">{{ $material->groups->count() }}</flux:badge>
                                    @endif
                                </flux:button>

                                <flux:menu>
                                    @forelse ($this->groups as $referenceGroup)
                                        <flux:menu.checkbox
                                            keep-open
                                            wire:key="material-{{ $material->id }}-group-{{ $referenceGroup->id }}"
                                            :checked="$material->groups->contains('id', $referenceGroup->id)"
                                            wire:click="toggleGroup({{ $referenceGroup->id }}, {{ $material->id }})"
                                        >{{ $referenceGroup->name }}</flux:menu.checkbox>
                                    @empty
                                        <flux:menu.item disabled>Nenhum grupo ainda</flux:menu.item>
                                    @endforelse
                                    <flux:menu.separator />
                                    <flux:menu.item icon="folder-plus" x-on:click="$flux.modal('add-group').show()">Novo grupo</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </div>
            @empty
                <x-empty-state
                    icon="book-open"
                    :heading="$this->activeGroup && blank($filter) && blank($type) ? 'Este grupo ainda não tem obras' : 'Nenhuma obra encontrada'"
                    :description="match (true) {
                        filled($filter) || filled($type) => 'Nenhuma obra corresponde aos filtros.',
                        $this->activeGroup !== null => 'Clique em Todos os grupos e use o botão Grupos no cartão de uma obra para colocá-la aqui.',
                        default => 'Adicione sua primeira obra à biblioteca.',
                    }"
                    class="col-span-full"
                />
            @endforelse
        </div>

        @if ($this->materials->hasPages())
            <flux:pagination :paginator="$this->materials" class="mt-10" />
        @endif
    </div>

    <flux:modal name="add-group" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
        <form wire:submit="addGroup" class="space-y-5">
            <div>
                <flux:heading size="lg">Novo grupo</flux:heading>
                <flux:text class="mt-2">Um espaço para juntar várias obras. A mesma obra pode estar em mais de um grupo.</flux:text>
            </div>

            <flux:input label="Nome do grupo" wire:model="groupForm.name" maxlength="60" placeholder="Ex: Teologia Sistemática" />

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Criar grupo</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-group" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Apagar o grupo {{ $this->activeGroup?->name }}?</flux:heading>
                <flux:text class="mt-2">Só o grupo será apagado. As obras continuam na sua biblioteca, nenhuma obra é apagada.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="deleteActiveGroup">Apagar grupo</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="add-material" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-lg">
        <form wire:submit="addMaterial" class="space-y-5">
            <div>
                <flux:heading size="lg">Nova obra</flux:heading>
                <flux:text class="mt-2">Catalogue um livro, artigo ou outra referência.</flux:text>
            </div>

            <flux:input label="Título" wire:model="form.title" placeholder="Ex: A Vida Juntos" />
            <flux:input label="Autor" wire:model="form.author" placeholder="Ex: Dietrich Bonhoeffer" />

            @include('partials.reference-type-pills', ['model' => 'form.type', 'label' => 'Tipo'])

            <div class="flex gap-3">
                <flux:input label="Ano" type="number" wire:model="form.year" placeholder="2005" class="w-28" />
                <flux:input label="Editora" wire:model="form.publisher" placeholder="Opcional" class="flex-1" />
            </div>

            <flux:input label="URL" wire:model="form.url" placeholder="https:// (opcional)" />
            <flux:textarea label="Referência ABNT" wire:model="form.abnt_reference" rows="2" placeholder="SOBRENOME, Nome. Título. Cidade: Editora, ano. (opcional — usada no cabeçalho da exportação)" />

            @include('partials.reference-reading-fields', ['model' => 'form'])

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Adicionar à biblioteca</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
