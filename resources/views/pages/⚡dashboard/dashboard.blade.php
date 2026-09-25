@placeholder

 <div class="mx-auto w-full max-w-7xl py-8">
        <div class="mb-8">
            <flux:heading size="xl" level="1">Disciplinas</flux:heading>
            <flux:text class="mt-2">Visão geral do progresso acadêmico.</flux:text>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach(range(1, 5) as $i)
                <div class="group border-surface-variant bg-surface-container-lowest hover:bg-surface-variant/40 relative flex h-48 flex-col justify-between overflow-hidden rounded-xl border p-6 shadow-sm transition-colors hover:shadow-md">

                <div class="primary bg-primary-container/10 absolute -top-4 -right-4 h-24 w-24 rounded-bl-full transition-transform group-hover:scale-110"></div>

                    <div class="absolute top-2 right-2 z-10">
                       <flux:skeleton class="size-5 rounded-full" />
                    </div>
                    <flux:skeleton class="size-10 rounded-full" />
                    <flux:skeleton class="h-5 w-3/4" />
                </div>
            @endforeach
        </div>
    
</div>
@endplaceholder

<div>
    <div class="mx-auto w-full max-w-7xl py-8">
        <div class="mb-8">
            <flux:heading size="xl" level="1">Disciplinas</flux:heading>
            <flux:text class="mt-2">Visão geral do progresso acadêmico.</flux:text>
        </div>

        <livewire:revisoes-do-dia />

        @if ($this->availablePeriods->isNotEmpty())
            <div class="mb-8 overflow-x-auto pb-1">
                <flux:radio.group wire:model.live="selectedPeriod" variant="segmented" class="flex-nowrap">
                    <flux:radio value="">Todas</flux:radio>

                    @foreach ($this->availablePeriods as $period)
                        <flux:radio value="{{ $period }}" wire:key="period-filter-{{ $period }}">{{ $period }}º</flux:radio>
                    @endforeach

                    @if ($this->hasDisciplinesWithoutPeriod())
                        <flux:radio value="sem">Sem período</flux:radio>
                    @endif
                </flux:radio.group>
            </div>
        @endif

        <div class="space-y-10">
            @foreach ($this->groupedDisciplines as $group)
                <section wire:key="period-group-{{ $group['period'] ?? 'sem' }}">
                    <div class="mb-4 flex items-center gap-3">
                        <flux:heading size="lg">{{ $group['label'] }}</flux:heading>
                        <flux:badge size="sm" variant="pill">{{ $group['disciplines']->count() }}</flux:badge>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($group['disciplines'] as $discipline)
                            <div
                                wire:key="discipline-{{ $discipline->id }}"
                                class="group border-surface-variant bg-surface-container-lowest hover:bg-surface-variant/40 relative flex h-48 flex-col justify-between overflow-hidden rounded-xl border p-6 shadow-sm transition-colors hover:shadow-md"
                            >
                                <a
                                    href="{{ route('disciplinas.show', $discipline->slug) }}"
                                    wire:navigate
                                    class="absolute inset-0 z-0"
                                    aria-label="{{ $discipline->title }}"
                                ></a>

                                <div class="primary bg-primary-container/10 absolute -top-4 -right-4 h-24 w-24 rounded-bl-full transition-transform group-hover:scale-110"></div>

                                <div class="absolute top-2 right-2 z-10 flex gap-1">
                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="ghost"
                                        icon="pencil-square"
                                        square
                                        class="opacity-50 transition-opacity hover:opacity-100"
                                        aria-label="Editar {{ $discipline->title }}"
                                        wire:click="openEditModal({{ $discipline->id }})"
                                    />

                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="ghost"
                                        icon="trash"
                                        square
                                        class="opacity-50 transition-opacity hover:opacity-100"
                                        aria-label="Remover {{ $discipline->title }}"
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

                                    @if ($discipline->code || $discipline->professor)
                                        <flux:text size="sm" class="text-on-surface-variant">
                                            {{ collect([$discipline->code, $discipline->professor])->filter()->implode(' · ') }}
                                        </flux:text>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
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
    </div>

    <flux:modal wire:model="showFormModal" name="disciplina-formulario" class="w-full max-w-sm">
        <form wire:submit="saveDiscipline" class="space-y-6">
            <flux:heading size="lg">{{ $isEditing ? 'Editar Disciplina' : 'Nova Disciplina' }}</flux:heading>

            @include('partials.discipline-fields')

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showFormModal', false)"
                    >Cancelar</flux:button>
                <flux:button type="submit" variant="primary">{{ $isEditing ? 'Salvar' : 'Criar' }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDeleteModal" name="excluir-disciplina" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
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
