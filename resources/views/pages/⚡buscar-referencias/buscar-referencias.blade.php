<x-slot:headerActions>
    <flux:button variant="ghost" icon="arrow-left" href="{{ route('referencias') }}" wire:navigate>
        Biblioteca
    </flux:button>
</x-slot:headerActions>

<div>
    <div class="mx-auto w-full max-w-4xl py-8">

        <flux:heading size="xl" level="1">Buscar nas referências</flux:heading>
        <flux:text class="mt-2">Pesquise pelas obras da biblioteca ou dentro do texto das citações.</flux:text>

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <flux:input
                icon="magnifying-glass"
                wire:model.live.debounce.400ms="q"
                placeholder="Digite um termo..."
                clearable
                class="flex-1"
            />
            <flux:radio.group wire:model.live="tab" variant="segmented">
                <flux:radio value="obras" label="Obras" />
                <flux:radio value="citacoes" label="Citações" />
            </flux:radio.group>
        </div>

        <div wire:loading.delay.flex class="hidden flex-col gap-3 mt-8">
            @for ($i = 0; $i < 4; $i++)
                <div class="rounded-xl border border-surface-variant bg-surface-container-lowest p-4 space-y-2">
                    <flux:skeleton class="h-4 w-1/3" />
                    <flux:skeleton class="h-4 w-full" />
                </div>
            @endfor
        </div>

        <div wire:loading.delay.remove class="mt-8">
            @if (blank($q))
                <div class="flex flex-col items-center justify-center py-20 px-6 text-center rounded-xl border border-dashed border-surface-variant bg-surface-container-low">
                    <flux:icon name="magnifying-glass" class="size-9 text-surface-variant-content/50 mb-3" />
                    <flux:text class="text-surface-variant-content">Comece a digitar para pesquisar.</flux:text>
                </div>
            @elseif ($tab === 'obras')
                @forelse ($this->works as $work)
                    @php($icon = \App\Enums\ReferencesIcon::tryFrom($work->type) ?? \App\Enums\ReferencesIcon::BookOpen)
                    <a href="{{ route('referencias.show', $work->id) }}" wire:navigate wire:key="work-{{ $work->id }}"
                       class="block rounded-xl border border-surface-variant bg-surface-container-lowest p-4 mb-3 hover:shadow-md transition-shadow">
                        <flux:badge size="sm" icon="{{ $icon->icon() }}" color="zinc">{{ $icon->label() }}</flux:badge>
                        <flux:heading class="mt-2">{{ $work->title }}</flux:heading>
                        <flux:text size="sm" class="mt-1">
                            {{ $work->author }}{{ $work->year ? ', '.$work->year : '' }} ·
                            {{ $work->citations_count }} {{ \Illuminate\Support\Str::plural('citação', $work->citations_count, 'citações') }}
                        </flux:text>
                    </a>
                @empty
                    <flux:text class="text-surface-variant-content">Nenhuma obra corresponde a "{{ $q }}".</flux:text>
                @endforelse
            @else
                <div class="mb-4 flex items-center justify-between">
                    <flux:text class="text-on-surface-variant">
                        {{ $this->citations->total() }} {{ \Illuminate\Support\Str::plural('resultado', $this->citations->total()) }}
                    </flux:text>
                    @if ($this->citations->total() > 0)
                        <flux:modal.trigger name="export-search">
                            <flux:button size="sm" variant="ghost" icon="arrow-down-tray">Exportar resultados</flux:button>
                        </flux:modal.trigger>
                    @endif
                </div>

                @forelse ($this->citations as $citation)
                    <div wire:key="cite-{{ $citation->id }}" class="rounded-xl border border-surface-variant bg-surface-container-lowest p-4 mb-3">
                        <p class="italic text-on-surface-variant leading-relaxed">&ldquo;{{ $citation->quote_text }}&rdquo;</p>
                        <div class="mt-2 flex items-center gap-2">
                            <flux:text size="sm" class="text-on-surface-variant/80">{{ $citation->location }}</flux:text>
                            @if ($citation->referenceMaterial)
                                <flux:spacer />
                                <flux:link href="{{ route('referencias.show', $citation->referenceMaterial->id) }}" wire:navigate class="text-sm">
                                    {{ $citation->referenceMaterial->title }}
                                </flux:link>
                            @endif
                        </div>
                    </div>
                @empty
                    <flux:text class="text-surface-variant-content">Nenhuma citação corresponde a "{{ $q }}".</flux:text>
                @endforelse

                @if ($this->citations->hasPages())
                    <flux:pagination :paginator="$this->citations" class="mt-6" />
                @endif
            @endif
        </div>

        <flux:modal name="export-search" class="md:w-96">
            <form wire:submit="exportSearch" class="space-y-5">
                <div>
                    <flux:heading size="lg">Exportar resultados</flux:heading>
                    <flux:text class="mt-2">Exporta todas as citações encontradas para "{{ $q }}", agrupadas por obra, em formato ABNT.</flux:text>
                </div>
                <flux:radio.group wire:model="exportFormat" label="Formato">
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
</div>
