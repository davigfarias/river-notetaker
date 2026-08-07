<div>
    <x-slot:headerActions>
        <flux:button variant="primary" icon="plus" href="{{ route('notas.criar') }}" wire:navigate> Nova Nota </flux:button>
    </x-slot:headerActions>

    <div class="-m-6 flex h-[calc(100vh-3.5rem)] lg:-m-8">
        <div class="border-outline-variant bg-surface-container-lowest/30 flex w-full flex-col overflow-y-auto border-r md:w-1/3 lg:w-1/4">
            <div class="border-outline-variant border-b p-4">
                <div class="border-outline-variant/30 bg-surface-container primary mb-4 flex h-12 w-12 items-center justify-center rounded-lg border">
                    <flux:icon :name="$disciplineDTO->icon" class="size-6" />
                </div>
                <flux:heading size="lg">{{ $disciplineDTO->title }}</flux:heading>
                <flux:text class="mt-1">Histórico de Notas</flux:text>
            </div>

            <div class="border-outline-variant border-b p-3">
                <flux:input
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar notas..."
                    size="sm"
                />
            </div>

            @forelse ($this->notes as $note)
                <button
                    type="button"
                    wire:key="note-{{ $note->id }}"
                    wire:click="selectNote({{ $note->id }})"
                    class="w-full border-b border-surface-variant p-4 text-left transition-colors hover:bg-surface-variant/50 {{ $this->selectedNote && $this->selectedNote->id === $note->id ? 'border-l-4 border-l-primary bg-surface-variant/30' : 'border-l-4 border-l-transparent' }}"
                >
                    <div class="mb-1 text-sm font-semibold {{ $this->selectedNote && $this->selectedNote->id === $note->id ? 'text-primary' : 'text-on-surface-variant' }}">
                        {{ $note->date() }}
                    </div>
                    <flux:heading size="base" class="mb-2 truncate">{{ $note->title }}</flux:heading>
                    <div class="flex flex-wrap gap-1">
                        @foreach ($note->tags ?? [] as $tag)
                            <flux:badge size="sm">#{{ $tag }}</flux:badge>
                        @endforeach
                    </div>
                </button>
            @empty
                <div class="p-4 text-center">
                    <flux:text size="sm">Nenhuma nota encontrada.</flux:text>
                </div>
            @endforelse

            <div class="mt-4 p-4 text-center">
                <flux:text size="sm">Fim do histórico.</flux:text>
            </div>
        </div>

        <div class="bg-surface hidden flex-1 flex-col overflow-y-auto p-8 md:flex lg:p-12">
            @if ($this->selectedNote)
                <div class="mx-auto w-full max-w-3xl pb-16">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <flux:heading
                                size="xl"
                                class="text-primary!"
                            >{{ $this->selectedNote->day() }}</flux:heading>
                            <flux:badge>{{ $this->selectedNote->year() }}</flux:badge>
                        </div>

                    </div>

                    <flux:heading size="xl" level="1" class="mb-4">{{ $this->selectedNote->title }}</flux:heading>

                    <div class="mb-8 flex flex-wrap gap-2">
                        @foreach ($this->selectedNote->tags ?? [] as $tag)
                            <flux:badge>#{{ $tag }}</flux:badge>
                        @endforeach
                    </div>

                    <div class="space-y-8">
                        <section>
                            <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                <flux:icon name="light-bulb" class="text-primary size-5" />
                                <flux:heading size="sm">CONCEITOS</flux:heading>
                            </div>
                            <div class="space-y-3">
                                @foreach ($this->selectedNote->concepts ?? [] as $concept)
                                    <div class="border-surface-variant bg-surface-container-lowest rounded-lg border p-3">
                                        <div class="font-semibold">{{ $concept->term }}</div>
                                        <flux:text class="mt-1">{{ $concept->definition }}</flux:text>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section>
                            <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                <flux:icon name="hand-raised" class="text-secondary size-5" />
                                <flux:heading size="sm">CONSELHOS PASTORAIS</flux:heading>
                            </div>
                            <div class="space-y-3">
                                @foreach($this->selectedNote->pastoral_advice ?? [] as $advice)
                                    <div class="border-surface-variant bg-surface-container-lowest rounded-lg border p-3">
                                        <div class="font-semibold">{{ $advice->category }}</div>
                                        <flux:text class="mt-1">{{ $advice->advice }}</flux:text>
                                    </div>
                                @endforeach
                            </div>
                        </section>


                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <section>
                                <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                    <flux:icon name="sparkles" class="text-tertiary size-5" />
                                    <flux:heading size="sm">IMPRESSÕES</flux:heading>
                                </div>
                                <div class="prose dark:prose-invert">
                                    {!! Str::markdown($this->selectedNote->impressions) !!}
                                </div>
                            </section>

                            <section>
                                <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                    <flux:icon name="book-open" class="text-on-surface-variant size-5" />
                                    <flux:heading size="sm">EXPERIÊNCIAS DE VIDA</flux:heading>
                                </div>
                                <div class="prose dark:prose-invert">
                                    {!! Str::markdown($this->selectedNote->life_experiences) !!}
                                </div>
                            </section>
                        </div>

                        <section>
                            <div class="border-surface-variant mb-3 flex items-center gap-2 border-b pb-2">
                                <flux:icon name="link" class="text-on-surface-variant size-5" />
                                <flux:heading size="sm">REFERÊNCIAS</flux:heading>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($this->selectedNote->references ?? [] as $reference)
                                    <div class="border-surface-variant bg-surface-container-lowest flex flex-col justify-between rounded-lg border p-3 shadow-sm transition-shadow hover:shadow-md h-full">

                                        @php
                                            $iconEnum = \App\Enums\ReferencesIcon::tryFrom($reference->type)
                                                        ?? \App\Enums\ReferencesIcon::BookOpen;
                                        @endphp

                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="bg-surface-variant/30 flex p-1.5 rounded-md">
                                                <flux:icon :name="$iconEnum->value" class="size-4 text-on-surface-variant" />
                                            </div>
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant/80">
                                                {{ $iconEnum->label() }}
                                            </span>
                                        </div>

                                        <div class="mt-auto">
                                            <flux:text class="text-xs font-medium leading-snug line-clamp-3" title="{{ $reference->reference_text }}">
                                                {{ $reference->reference_text }}
                                            </flux:text>
                                        </div>

                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </div>
                </div>
            @else
                <div class="flex flex-1 items-center justify-center">
                    <flux:text>Selecione uma nota para visualizar.</flux:text>
                </div>
            @endif
        </div>
    </div>
</div>
