
<div class="mx-auto w-full max-w-4xl py-8">

    <flux:heading size="xl" level="1">Buscar</flux:heading>
    <flux:text class="mt-2">Pesquise em notas, conselhos pastorais, conceitos, referências e citações.</flux:text>

    <div class="mt-6">
        <flux:input
            icon="magnifying-glass"
            wire:model.live.debounce.400ms="q"
            placeholder="Digite um termo..."
            clearable
            autofocus
        />
    </div>

    <div class="mt-8">
        @if (blank($q))
            <x-empty-state
                icon="magnifying-glass"
                heading="Digite um termo pra começar"
                description="A busca cobre notas, conselhos pastorais, conceitos, referências e citações."
            />
        @elseif ($this->results->isEmpty())
            @php($noResultsDescription = 'Nada corresponde à busca "'.$q.'".')
            <x-empty-state
                icon="document-magnifying-glass"
                heading="Nenhum resultado encontrado"
                :description="$noResultsDescription"
            />
        @else
            <div class="flex flex-col gap-10">
                @foreach ($this->orderedTypes as $type)
                    @continue (! $this->results->has($type->value))

                    <div wire:key="group-{{ $type->value }}">
                        <flux:heading size="lg" level="2">{{ $type->label() }}</flux:heading>
                        <flux:separator class="my-4" />

                        <div class="flex flex-col gap-3">
                            @foreach ($this->results[$type->value] as $result)
                                <a
                                    wire:key="result-{{ $type->value }}-{{ $result->id }}"
                                    href="{{ $result->url }}"
                                    wire:navigate
                                    class="flex items-start gap-3 rounded-xl border border-surface-variant bg-surface-container-lowest p-4 transition-colors hover:bg-surface-variant/40"
                                >
                                    <flux:icon name="{{ $type->icon() }}" class="mt-0.5 size-5 shrink-0 text-on-surface-variant" />
                                    <div class="min-w-0 flex-1">
                                        <flux:text class="block font-medium text-on-surface">{{ $result->title }}</flux:text>
                                        @if ($result->snippet)
                                            <flux:text size="sm" class="mt-1 block text-on-surface-variant">{{ $result->snippet }}</flux:text>
                                        @endif
                                    </div>
                                    <flux:icon name="chevron-right" class="mt-0.5 size-4 shrink-0 text-on-surface-variant" />
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
