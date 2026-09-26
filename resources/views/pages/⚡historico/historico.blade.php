@placeholder
    <div>
        <div class="mx-auto w-full max-w-7xl py-8">
            <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <flux:heading size="xl" level="1">Histórico</flux:heading>
                    <flux:text class="mt-2">Eventos, obras e pessoas em ordem cronológica.</flux:text>
                </div>
            </div>

            <div class="space-y-6 rounded-xl border border-outline-variant bg-surface-container-low p-4">
                @foreach (range(1, 6) as $i)
                    <flux:skeleton.group animate="shimmer" class="flex items-center gap-3">
                        <flux:skeleton class="size-8 rounded-full" />
                        <div class="flex-1 space-y-2">
                            <flux:skeleton.line class="w-1/4" />
                            <flux:skeleton.line class="w-2/3" />
                        </div>
                    </flux:skeleton.group>
                @endforeach
            </div>
        </div>
    </div>
@endplaceholder

<div>
    <div class="mx-auto w-full max-w-7xl py-8">

        <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <flux:heading size="xl" level="1">Histórico</flux:heading>
                <flux:text class="mt-2">Eventos, obras e pessoas em ordem cronológica.</flux:text>
            </div>

            <div>
                <flux:modal.trigger name="add-event">
                    <flux:button icon="plus">Evento</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <flux:input name="search" icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por título, descrição ou ano..." clearable class="mb-4 max-w-md" />

        @if ($this->events->isEmpty())
            @php
                $noEventsHeading = filled($search) ? 'Nada encontrado para "'.$search.'"' : 'Nenhum evento registrado';
                $noEventsDescription = filled($search)
                    ? 'Tente outro termo ou um ano, como 1517.'
                    : 'Cadastre o primeiro evento para começar a sua linha do tempo.';
            @endphp
            <x-empty-state
                :icon="filled($search) ? 'magnifying-glass' : 'clock'"
                :heading="$noEventsHeading"
                :description="$noEventsDescription"
            />
        @else
            <div class="max-h-[70vh] overflow-y-auto rounded-xl border border-outline-variant bg-surface-container-low p-4 sm:p-6" data-historico-box>
                <x-timeline align="start">
                    @foreach ($this->events as $event)
                        <x-timeline.item wire:key="event-{{ $event->id }}">
                            <x-timeline.indicator :color="$event->nature->color()">
                                <flux:icon :name="$event->nature->icon()" variant="micro" />
                            </x-timeline.indicator>

                            <x-timeline.content class="group">
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="text-sm font-semibold tabular-nums text-primary">{{ $event->periodLabel() }}</span>
                                            <flux:badge size="sm" :color="$event->nature->color()">{{ $event->nature->label() }}</flux:badge>
                                        </div>
                                        <flux:heading class="mt-1">{{ $event->title }}</flux:heading>
                                        @if (filled($event->description))
                                            <flux:text class="mt-1 line-clamp-3">{{ $event->description }}</flux:text>
                                        @endif
                                    </div>

                                    <div class="flex shrink-0 gap-1">
                                        <flux:button size="sm" variant="subtle" square icon="pencil-square"
                                            wire:click="editEvent({{ $event->id }})" aria-label="Editar evento" />
                                        <flux:button size="sm" variant="subtle" square icon="trash"
                                            wire:click="confirmDeleteEvent({{ $event->id }})" aria-label="Remover evento" />
                                    </div>
                                </div>
                            </x-timeline.content>
                        </x-timeline.item>
                    @endforeach
                </x-timeline>
            </div>

            <flux:text class="mt-3 text-xs">{{ $this->events->count() }} {{ \Illuminate\Support\Str::plural('registro', $this->events->count()) }}</flux:text>
        @endif
    </div>

    <flux:modal name="add-event" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-lg">
        <form wire:submit="addEvent" class="space-y-4">
            <flux:heading size="lg">Novo evento</flux:heading>

            @include('partials.historical-event-fields', ['model' => 'eventForm'])

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-event" wire:model.self="editingEvent" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-lg">
        <form wire:submit="updateEvent" class="space-y-4">
            <flux:heading size="lg">Editar evento</flux:heading>

            @include('partials.historical-event-fields', ['model' => 'editEventForm'])

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-event" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remover evento</flux:heading>
                <flux:text class="mt-2">Esta ação não pode ser desfeita.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="deleteEvent">Remover</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
