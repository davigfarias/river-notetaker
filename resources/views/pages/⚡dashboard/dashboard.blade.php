<x-slot:headerActions>
    <flux:button variant="primary" icon="plus" href="{{ route('notas.criar') }}" wire:navigate> Nova Nota </flux:button>
</x-slot:headerActions>

<div>
    <div class="mx-auto w-full max-w-7xl py-8">
        <div class="mb-8">
            <flux:heading size="xl" level="1">Disciplinas</flux:heading>
            <flux:text class="mt-2">Visão geral do progresso acadêmico.</flux:text>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->disciplines as $discipline)
                <div
                    wire:key="discipline-{{ $discipline->slug }}"
                    class="group border-surface-variant bg-surface-container-low hover:bg-surface-variant/40 relative flex h-48 flex-col justify-between overflow-hidden rounded-xl border p-6 shadow-sm transition-colors hover:shadow-md"
                >
                    <a
                        href="{{ route('disciplinas.show', $discipline->slug) }}"
                        wire:navigate
                        class="absolute inset-0 z-0"
                        aria-label="{{ $discipline->title }}"
                    ></a>

                    <div class="primary bg-primary-container/10 absolute -top-4 -right-4 h-24 w-24 rounded-bl-full transition-transform group-hover:scale-110"></div>

                    <div class="absolute top-2 right-2 z-10">
                        <flux:button
                            type="button"
                            size="xs"
                            variant="ghost"
                            icon="trash"
                            square
                            class="opacity-50 transition-opacity hover:opacity-100"
                            wire:click="openDeleteModal({{ $discipline->id }})"
                        />
                    </div>

                    <div class="pointer-events-none relative z-1">
                        <div class="border-outline-variant/30 bg-surface-container primary mb-4 flex h-12 w-12 items-center justify-center rounded-lg border">
                            <flux:icon :name="$discipline->icon" class="size-6" />
                        </div>
                        <flux:heading size="lg" class="'group-hover:text-primary' mb-1 transition-colors">
                            {{ $discipline->title }}
                        </flux:heading>
                    </div>
                </div>
            @endforeach

            <button
                type="button"
                wire:click="openCreateModal"
                class="group border-outline-variant/50 hover:border-primary/50 hover:bg-surface-variant/20 flex h-48 flex-col items-center justify-center rounded-xl border-2 border-dashed p-6 text-center transition-colors"
            >
                <div class="bg-surface-container group-hover:bg-primary-container/20 mb-3 flex h-12 w-12 items-center justify-center rounded-full transition-colors">
                    <flux:icon
                        name="plus"
                        class="text-outline-variant group-hover:text-primary size-6 transition-colors"
                    />
                </div>
                <flux:text class="text-on-surface-variant group-hover:text-on-surface transition-colors">Nova Disciplina</flux:text>
            </button>
        </div>
    </div>

    <flux:modal wire:model="showCreateModal" name="nova-disciplina" class="w-full max-w-sm">
        <form wire:submit="addDiscipline" class="space-y-6">
            <flux:heading size="lg">Nova Disciplina</flux:heading>

            <flux:field>
                <flux:label>Nome da disciplina</flux:label>
                <flux:input wire:model="dto.title" placeholder="Ex: Homilética" autofocus />
                <flux:error name="dto.title" />
            </flux:field>

            <flux:field>
                <flux:label>Ícone</flux:label>
                <div class="overflow-x-auto pb-1">
                    <flux:radio.group wire:model="dto.icon" variant="buttons" class="flex-nowrap">
                        @foreach (App\Enums\DisciplineIcon::options() as $icon)
                            <flux:radio value="{{ $icon }}" icon="{{ $icon }}" wire:key="discipline-icon-{{ $icon }}" />
                        @endforeach
                    </flux:radio.group>
                </div>
                <flux:error name="dto.icon" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showCreateModal', false)"
                    >Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Criar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDeleteModal" name="excluir-disciplina" class="min-w-88">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remover disciplina?</flux:heading>
                <flux:text class="mt-2">
                    Você está prestes a remover essa disciplina. Essa ação não pode ser desfeita.
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button
                    type="button"
                    variant="ghost"
                    wire:click="$set('showDeleteModal', false)"
                >
                    Cancelar
                </flux:button>

                <flux:button
                    type="button"
                    variant="danger"
                    wire:click="deleteDiscipline"
                >
                    Remover disciplina
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
