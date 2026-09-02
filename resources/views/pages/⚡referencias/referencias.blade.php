<x-slot:headerActions>
    <flux:button variant="ghost" icon="arrow-down-tray" href="{{ route('referencias.exportacoes') }}" wire:navigate>
        <span class="hidden sm:inline">Exportações</span>
    </flux:button>
    <flux:button variant="primary" icon="plus" href="{{ route('notas.criar') }}" wire:navigate>
        Nova Nota
    </flux:button>
</x-slot:headerActions>

@placeholder
    <x-slot:headerActions>
        <flux:button variant="ghost" icon="arrow-down-tray" href="{{ route('referencias.exportacoes') }}" wire:navigate>
            <span class="hidden sm:inline">Exportações</span>
        </flux:button>
        <flux:button variant="primary" icon="plus" href="{{ route('notas.criar') }}" wire:navigate>
            Nova Nota
        </flux:button>
    </x-slot:headerActions>

    <div class="mx-auto w-full max-w-7xl py-8">
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

<div class="mx-auto w-full max-w-7xl py-8">

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
    </div>

    <div wire:loading.delay.flex wire:target="filter,type,previousPage,nextPage,gotoPage" class="hidden flex-wrap gap-4">
        @for ($i = 0; $i < 6; $i++)
            <div class="w-full sm:w-[calc(50%-0.5rem)] lg:w-[calc(33.333%-0.75rem)] rounded-xl border border-surface-variant bg-surface-container-lowest p-4 space-y-3">
                <flux:skeleton class="h-4 w-20" />
                <flux:skeleton class="h-5 w-3/4" />
                <flux:skeleton class="h-4 w-1/2" />
            </div>
        @endfor
    </div>

    <div wire:loading.delay.remove wire:target="filter,type,previousPage,nextPage,gotoPage">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($this->materials as $material)
                @php($icon = \App\Enums\ReferencesIcon::tryFrom($material->type) ?? \App\Enums\ReferencesIcon::BookOpen)
                <a
                    href="{{ route('referencias.show', $material->id) }}"
                    wire:navigate
                    wire:key="material-{{ $material->id }}"
                    class="group flex flex-col justify-between rounded-xl border border-surface-variant bg-surface-container-lowest p-4 shadow-sm transition-shadow hover:shadow-md"
                >
                    <div>
                        <flux:badge size="sm" icon="{{ $icon->icon() }}" color="zinc">{{ $icon->label() }}</flux:badge>
                        <flux:heading class="mt-3 leading-snug group-hover:text-primary">{{ $material->title }}</flux:heading>
                        @if ($material->author)
                            <flux:text size="sm" class="mt-1">{{ $material->author }}{{ $material->year ? ', '.$material->year : '' }}</flux:text>
                        @endif
                    </div>
                    <flux:text size="sm" class="mt-4 text-on-surface-variant">
                        {{ $material->citations_count }} {{ \Illuminate\Support\Str::plural('citação', $material->citations_count, 'citações') }}
                    </flux:text>
                </a>
            @empty
                <div class="col-span-full flex flex-col items-center justify-center py-24 px-6 text-center rounded-xl border border-dashed border-surface-variant bg-surface-container-low">
                    <flux:icon name="book-open" class="size-10 text-surface-variant-content/50 mb-3" />
                    <flux:heading size="md">Nenhuma obra encontrada</flux:heading>
                    <flux:text class="mt-2 text-surface-variant-content">
                        @if (filled($filter) || filled($type))
                            Nenhuma obra corresponde aos filtros.
                        @else
                            Adicione sua primeira obra à biblioteca.
                        @endif
                    </flux:text>
                </div>
            @endforelse
        </div>

        @if ($this->materials->hasPages())
            <flux:pagination :paginator="$this->materials" class="mt-10" />
        @endif
    </div>

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

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Adicionar à biblioteca</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
