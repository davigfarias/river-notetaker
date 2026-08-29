<x-slot:headerActions>
    <flux:button variant="ghost" icon="arrow-left" href="{{ route('referencias') }}" wire:navigate>
        Biblioteca
    </flux:button>
</x-slot:headerActions>

@php($material = $ready ? $this->material : null)
@php($icon = $material ? (\App\Enums\ReferencesIcon::tryFrom($material->type) ?? \App\Enums\ReferencesIcon::BookOpen) : null)

<div wire:init="loadContent">
    <div class="mx-auto w-full max-w-4xl py-8">

        @if (! $ready || ! $material)
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
        @else
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <flux:badge size="sm" icon="{{ $icon->icon() }}" color="zinc">{{ $icon->label() }}</flux:badge>
                    <flux:heading size="xl" level="1" class="mt-3">{{ $material->title }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ $material->author }}{{ $material->year ? ' · '.$material->year : '' }}{{ $material->publisher ? ' · '.$material->publisher : '' }}
                    </flux:text>
                    @if ($material->url)
                        <flux:link href="{{ $material->url }}" target="_blank" class="mt-1 block text-sm">{{ $material->url }}</flux:link>
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
                    {{ app(\App\Support\Export\AbntFormatter::class)->reference($material) }}
                </flux:text>
            </div>

            <flux:separator class="my-8" />

            <flux:heading size="lg" level="2">
                Citações
                <flux:badge size="sm" class="ml-1">{{ $material->citations_count }}</flux:badge>
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
                @forelse ($material->citations as $citation)
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
        @endif

        <flux:modal name="edit-material" wire:model.self="editingMaterial" class="md:w-[32rem]">
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

        <flux:modal name="edit-citation" wire:model.self="editingCitation" class="md:w-[32rem]">
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

        <flux:modal name="delete-citation" class="md:w-96">
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

        <flux:modal name="export" class="md:w-96">
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
</div>
