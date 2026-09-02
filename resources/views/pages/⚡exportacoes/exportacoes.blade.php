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
        <flux:heading size="xl" level="1">Exportações</flux:heading>
        <flux:text class="mt-2">Arquivos gerados a partir das suas citações. Ficam disponíveis por {{ config('exports.retention_days') }} dias.</flux:text>

        <div class="mt-8 space-y-3">
            @for ($i = 0; $i < 4; $i++)
                <div class="flex items-center gap-4 rounded-xl border border-surface-variant bg-surface-container-lowest p-4">
                    <flux:skeleton class="size-8 shrink-0 rounded" />
                    <div class="flex-1 space-y-2">
                        <flux:skeleton class="h-4 w-1/2" />
                        <flux:skeleton class="h-3 w-1/4" />
                    </div>
                    <flux:skeleton class="h-6 w-24 shrink-0" />
                </div>
            @endfor
        </div>
    </div>
@endplaceholder

<div class="mx-auto w-full max-w-4xl py-8" @if ($this->hasInProgress) wire:poll.5s @endif>

    <flux:heading size="xl" level="1">Exportações</flux:heading>
    <flux:text class="mt-2">Arquivos gerados a partir das suas citações. Ficam disponíveis por {{ config('exports.retention_days') }} dias.</flux:text>

    <div class="mt-8 space-y-3">
        @forelse ($this->exports as $export)
            <div wire:key="export-{{ $export->id }}" class="flex items-center gap-4 rounded-xl border border-surface-variant bg-surface-container-lowest p-4">
                <flux:icon name="{{ $export->format->value === 'pdf' ? 'document' : 'document-text' }}" class="size-8 text-on-surface-variant shrink-0" />

                <div class="min-w-0 flex-1">
                    <flux:text class="font-medium truncate">
                        @if ($export->scope->value === 'search')
                            Busca: &ldquo;{{ $export->search_query }}&rdquo;
                        @else
                            {{ $export->referenceMaterial?->title ?? 'Obra removida' }}
                        @endif
                    </flux:text>
                    <flux:text size="sm" class="text-on-surface-variant">
                        {{ strtoupper($export->format->value) }} · {{ $export->created_at->diffForHumans() }}
                    </flux:text>
                </div>

                <div class="shrink-0 w-24 sm:w-28">
                    @if ($export->status->isInProgress())
                        <flux:skeleton class="h-6 w-24" animate="shimmer" />
                    @elseif ($export->status === \App\Enums\ExportStatus::Completed)
                        <flux:badge size="sm" color="green" icon="check">{{ $export->status->label() }}</flux:badge>
                    @elseif ($export->status === \App\Enums\ExportStatus::Failed)
                        <flux:badge size="sm" color="red" icon="x-mark">{{ $export->status->label() }}</flux:badge>
                    @else
                        <flux:badge size="sm" color="zinc">{{ $export->status->label() }}</flux:badge>
                    @endif
                </div>

                <div class="shrink-0 flex gap-1">
                    @if ($export->status->isDownloadable())
                        <flux:button size="sm" variant="ghost" icon="arrow-down-tray"
                                     href="{{ route('referencias.exportacoes.download', $export) }}"
                                     target="_blank"><span class="hidden sm:inline">Baixar</span></flux:button>
                    @endif
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDeleteExport({{ $export->id }})" />
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-24 px-6 text-center rounded-xl border border-dashed border-surface-variant bg-surface-container-low">
                <flux:icon name="arrow-down-tray" class="size-10 text-surface-variant-content/50 mb-3" />
                <flux:heading size="md">Nenhuma exportação ainda</flux:heading>
                <flux:text class="mt-2 text-surface-variant-content">
                    Exporte as citações de uma obra ou de uma busca para vê-las aqui.
                </flux:text>
            </div>
        @endforelse
    </div>

    @if ($this->exports->hasPages())
        <flux:pagination :paginator="$this->exports" class="mt-8" />
    @endif

    <flux:modal name="delete-export" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remover exportação</flux:heading>
                <flux:text class="mt-2">O arquivo é apagado do armazenamento. Esta ação não pode ser desfeita.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="deleteExport">Remover</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
