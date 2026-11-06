<div class="w-full py-8">
    @php($planner = $this->planner)
    @php($progress = $planner->progress())
    @php($complete = $progress['total'] > 0 && $progress['read'] === $progress['total'])

    <flux:button variant="ghost" size="sm" icon="arrow-left" href="{{ route('referencias.planners') }}" wire:navigate class="mb-6">Planners</flux:button>

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div class="min-w-0">
            <flux:heading size="xl" level="1">{{ $planner->name }}</flux:heading>
            @if ($planner->objective)
                <flux:text class="mt-2 max-w-prose">{{ $planner->objective }}</flux:text>
            @endif
        </div>

        <div class="flex shrink-0 gap-2">
            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openEdit">Editar</flux:button>
            <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete">Excluir</flux:button>
        </div>
    </div>

    {{-- Progresso: um segmento por item, aceso a cada check. --}}
    <section class="border-surface-variant bg-surface-container-lowest mt-8 rounded-xl border p-5" aria-label="Progresso do planner">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <div class="flex items-baseline gap-3">
                <span class="text-on-surface text-4xl font-semibold tabular-nums">{{ $progress['percent'] }}%</span>
                <flux:text class="tabular-nums">
                    @if ($progress['total'] === 0)
                        Nenhum item no planner
                    @else
                        {{ $progress['read'] }} de {{ $progress['total'] }} {{ $progress['total'] === 1 ? 'item lido' : 'itens lidos' }}
                    @endif
                </flux:text>
            </div>

            @if ($complete)
                <flux:badge color="green" icon="check-circle">Concluído</flux:badge>
            @elseif ($planner->target_date)
                @if ($planner->target_date->isPast() && ! $planner->target_date->isToday())
                    <flux:badge color="amber" icon="clock">Meta passou em {{ $planner->target_date->format('d/m/Y') }}</flux:badge>
                @else
                    <flux:badge color="zinc" icon="calendar">Meta: {{ $planner->target_date->format('d/m/Y') }}</flux:badge>
                @endif
            @endif
        </div>

        <x-planner-progress :read="$progress['read']" :total="$progress['total']" height="h-4" class="mt-4" />
    </section>

    <div class="mt-8 flex items-center justify-between gap-3">
        <flux:heading size="lg" level="2">O que ler</flux:heading>
        <flux:button size="sm" variant="primary" icon="plus" wire:click="openAdd">Adicionar</flux:button>
    </div>

    <div class="mt-4 space-y-4">
        @forelse ($this->groups as $items)
            @php($material = $items->first()->referenceMaterial)
            @php($groupRead = $items->filter->isRead()->count())
            <section wire:key="group-{{ $material->id }}" class="border-surface-variant bg-surface-container-lowest rounded-xl border">
                <header class="border-surface-variant flex items-baseline justify-between gap-3 border-b px-4 py-3">
                    <div class="min-w-0">
                        <flux:heading size="sm" class="truncate">{{ $material->title }}</flux:heading>
                        @if ($material->author)
                            <flux:text size="sm">{{ $material->author }}</flux:text>
                        @endif
                    </div>
                    <flux:text size="sm" class="shrink-0 tabular-nums">{{ $groupRead }}/{{ $items->count() }}</flux:text>
                </header>

                <ul class="divide-surface-variant divide-y">
                    @foreach ($items as $item)
                        <li wire:key="item-{{ $item->id }}" class="group flex items-center gap-3 px-4 py-2.5">
                            <flux:checkbox
                                :checked="$item->isRead()"
                                wire:click="toggle({{ $item->id }})"
                                aria-label="Marcar {{ $item->label() }} como lido"
                            />
                            <span @class([
                                'min-w-0 flex-1 break-words text-sm transition-colors',
                                'text-on-surface-variant line-through' => $item->isRead(),
                                'text-on-surface' => ! $item->isRead(),
                            ])>
                                {{ $item->chapter_id === null ? 'Obra inteira' : $item->label() }}
                            </span>
                            @if ($item->isRead())
                                <flux:text size="sm" class="shrink-0 tabular-nums">lido em {{ $item->read_at->format('d/m') }}</flux:text>
                            @endif
                            <flux:button size="xs" variant="ghost" icon="x-mark" aria-label="Retirar do planner"
                                class="opacity-0 transition-opacity group-hover:opacity-100 max-lg:opacity-100"
                                wire:click="confirmRemove({{ $item->id }})" />
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <x-empty-state
                icon="map"
                heading="Este planner ainda está vazio."
                description="Escolha as obras e os capítulos que você quer ler para cumprir o objetivo."
            >
                <flux:button variant="primary" icon="plus" wire:click="openAdd">Adicionar obras e capítulos</flux:button>
            </x-empty-state>
        @endforelse
    </div>

    {{-- Editar nome, objetivo e data-alvo. --}}
    <flux:modal name="edit-planner" wire:model.self="editing" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-md">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">Editar planner</flux:heading>
            <flux:input label="Nome" wire:model="form.name" />
            <flux:error name="form.name" />
            <flux:textarea label="Objetivo (opcional)" wire:model="form.objective" rows="3" />
            <flux:error name="form.objective" />
            <flux:input type="date" label="Data-alvo (opcional)" wire:model="form.targetDate" />
            <flux:error name="form.targetDate" />
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Seletor de obras e capítulos: painel lateral, a barra do planner continua à vista e avança a cada adição. --}}
    <flux:modal name="add-items" flyout wire:model.self="adding" class="w-full max-w-[calc(100vw-2rem)] md:w-[28rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Adicionar ao planner</flux:heading>
                <flux:text class="mt-2">Marque os capítulos (ou as obras sem capítulos) que entram em “{{ $planner->name }}”.</flux:text>
            </div>

            <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Buscar por título ou autor..." clearable />

            <form id="add-items-form" wire:submit="addItems">
                <flux:checkbox.group wire:model.live="selection" class="space-y-6">
                    @forelse ($this->library as $material)
                        <div wire:key="pick-{{ $material->id }}">
                            <flux:heading size="sm">{{ $material->title }}</flux:heading>
                            @if ($material->author)
                                <flux:text size="sm">{{ $material->author }}</flux:text>
                            @endif

                            <div class="border-surface-variant mt-2 space-y-2 border-s-2 ps-3">
                                @forelse ($material->chapters as $chapter)
                                    <flux:checkbox
                                        wire:key="pick-c-{{ $chapter->id }}"
                                        value="c{{ $chapter->id }}"
                                        label="{{ $chapter->title }}"
                                        :disabled="in_array('c'.$chapter->id, $this->taken, true)"
                                        :description="in_array('c'.$chapter->id, $this->taken, true) ? 'Já está no planner' : null"
                                    />
                                @empty
                                    <flux:checkbox
                                        wire:key="pick-m-{{ $material->id }}"
                                        value="m{{ $material->id }}"
                                        label="Obra inteira"
                                        :disabled="in_array('m'.$material->id, $this->taken, true)"
                                        :description="in_array('m'.$material->id, $this->taken, true) ? 'Já está no planner' : 'Sem capítulos cadastrados: entra como 1 item. Para dividir, cadastre capítulos na obra.'"
                                    />
                                @endforelse
                            </div>
                        </div>
                    @empty
                        @if (trim($search) !== '')
                            <flux:text>Nenhuma obra encontrada para “{{ $search }}”. Tente outro título ou autor.</flux:text>
                        @else
                            <flux:text>Você ainda não tem obras. Cadastre uma na aba Obras e volte aqui.</flux:text>
                        @endif
                    @endforelse
                </flux:checkbox.group>
            </form>
        </div>

        <x-slot name="footer" class="flex items-center gap-2">
            <flux:text size="sm" class="tabular-nums">{{ count($selection) }} {{ count($selection) === 1 ? 'selecionado' : 'selecionados' }}</flux:text>
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Fechar</flux:button>
            </flux:modal.close>
            <flux:button type="submit" form="add-items-form" variant="primary" icon="plus" :disabled="count($selection) === 0">
                {{ count($selection) > 0 ? 'Adicionar '.count($selection) : 'Adicionar' }}
            </flux:button>
        </x-slot>
    </flux:modal>

    <flux:modal name="remove-item" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Retirar do planner?</flux:heading>
                <flux:text class="mt-2">O item sai do planner e a barra de progresso se ajusta. A obra e o capítulo continuam na sua biblioteca.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button variant="danger" icon="x-mark" wire:click="removeItem">Retirar</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="delete-planner" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Excluir planner?</flux:heading>
                <flux:text class="mt-2">O planner e as marcações de leitura dele serão apagados. As obras e os capítulos continuam na sua biblioteca. Esta ação não pode ser desfeita.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="deletePlanner">Excluir</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
