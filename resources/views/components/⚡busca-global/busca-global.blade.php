
<div
    wire:keydown.cmd.k.window.prevent="open"
    wire:keydown.ctrl.k.window.prevent="open"
>
    <flux:button
        wire:click="open"
        variant="ghost"
        icon="magnifying-glass"
        size="sm"
        aria-label="Buscar em todo o site"
    />

    <flux:modal name="busca-global" wire:model.self="show" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Buscar</flux:heading>

            <flux:input
                icon="magnifying-glass"
                wire:model.live.debounce.300ms="q"
                autofocus
                clearable
                placeholder="Buscar em notas, conselhos, referências..."
            />

            @if (blank($q))
                <flux:text class="text-on-surface-variant text-center py-6">
                    Digite pra buscar em notas, conselhos pastorais, conceitos, referências e citações.
                </flux:text>
            @elseif ($this->results->isEmpty())
                <flux:text class="text-on-surface-variant text-center py-6">
                    Nenhum resultado encontrado para "{{ $q }}".
                </flux:text>
            @else
                <div class="flex flex-col gap-1">
                    @foreach ($this->results as $result)
                        <a
                            wire:key="global-result-{{ $result->type->value }}-{{ $result->id }}"
                            href="{{ $result->url }}"
                            wire:navigate
                            wire:click="close"
                            class="flex items-start gap-3 rounded-lg p-3 hover:bg-surface-variant/40 transition-colors"
                        >
                            <flux:icon name="{{ $result->type->icon() }}" class="mt-0.5 size-5 shrink-0 text-on-surface-variant" />
                            <div class="min-w-0">
                                <flux:text class="block truncate font-medium text-on-surface">{{ $result->title }}</flux:text>
                                @if ($result->snippet)
                                    <flux:text size="sm" class="line-clamp-2 text-on-surface-variant">{{ $result->snippet }}</flux:text>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <flux:button
                    href="{{ route('busca', ['q' => $q]) }}"
                    wire:navigate
                    wire:click="close"
                    variant="ghost"
                    class="w-full"
                >
                    Ver todos os resultados
                </flux:button>
            @endif
        </div>
    </flux:modal>
</div>
