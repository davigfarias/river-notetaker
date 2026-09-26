@placeholder
    <div class="mx-auto w-full max-w-4xl py-8 space-y-6">
        <flux:skeleton class="h-8 w-64" />
        <div class="flex justify-center gap-4">
            <flux:skeleton class="h-24 w-40" />
            <flux:skeleton class="h-24 w-40" />
        </div>
        <flux:skeleton class="h-32 w-full" />
        <flux:skeleton class="h-32 w-full" />
    </div>
@endplaceholder

<div>
    <div class="mx-auto w-full max-w-4xl py-8">

        <div class="mb-8">
            <flux:text class="uppercase tracking-wide text-on-surface-variant">Tema</flux:text>
            <flux:heading size="xl" level="1">{{ $this->topic->title }}</flux:heading>

            @if ($this->topic->disciplines->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-1">
                    @foreach ($this->topic->disciplines as $discipline)
                        <flux:badge size="sm">{{ $discipline->title }}</flux:badge>
                    @endforeach
                </div>
            @else
                <flux:text class="mt-3 text-sm text-on-surface-variant">
                    Nenhuma disciplina usa este tema ainda. Vincule pela tela da disciplina.
                </flux:text>
            @endif
        </div>

        <div class="mb-10 flex flex-wrap justify-center gap-4">
            <flux:modal.trigger name="add-concept-principle">
                <button
                    type="button"
                    class="flex w-40 flex-col items-center justify-center gap-2 rounded-xl border border-surface-variant bg-surface-container-low p-6 text-center font-semibold hover:bg-surface-variant/40 transition-colors"
                >
                    <flux:icon name="light-bulb" class="size-6 text-primary" />
                    Conceito
                </button>
            </flux:modal.trigger>

            <button
                type="button"
                wire:click="startAddingText"
                class="flex w-40 flex-col items-center justify-center gap-2 rounded-xl border border-surface-variant bg-surface-container-low p-6 text-center font-semibold hover:bg-surface-variant/40 transition-colors"
            >
                <flux:icon name="scale" class="size-6 text-primary" />
                Princípio
            </button>
        </div>

        @if ($addingText)
            <form wire:submit="addText" class="mb-4 space-y-4 rounded-xl border border-primary/40 bg-surface-container-lowest p-6">
                <flux:input label="Título" wire:model="textForm.title" placeholder="Ex: Sola Gratia" />
                <flux:error name="textForm.title" />

                <div wire:ignore>
                    <div x-data="markdownEditor('textForm.body')">
                        <textarea x-ref="textarea" placeholder="Princípio + comentários..."></textarea>
                    </div>
                </div>
                <flux:error name="textForm.body" />

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="cancelAddingText">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Adicionar</flux:button>
                </div>
            </form>
        @endif

        @if ($this->topic->principles->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 px-6 text-center rounded-xl border border-dashed border-surface-variant bg-surface-container-low">
                <flux:icon name="scale" class="size-9 text-surface-variant-content/50 mb-3" />
                <flux:text class="text-surface-variant-content">Nenhum princípio adicionado a este tema ainda.</flux:text>
            </div>
        @else
            <x-timeline align="start">
                @foreach ($this->topic->principles as $principle)
                    <x-timeline.item wire:key="principle-{{ $principle->id }}">
                        @if ($principle->type === \App\Enums\PrincipleType::Concept)
                            <x-timeline.indicator color="blue">
                                <flux:icon name="light-bulb" variant="micro" />
                            </x-timeline.indicator>
                        @else
                            <x-timeline.indicator color="violet">
                                <flux:icon name="scale" variant="micro" />
                            </x-timeline.indicator>
                        @endif

                        @if ($editingPrincipleId === $principle->id)
                            <x-timeline.content>
                                <form wire:submit="updateText" class="space-y-4 rounded-xl border border-primary/40 bg-surface-container-lowest p-6">
                                    <flux:input label="Título" wire:model="textForm.title" placeholder="Ex: Sola Gratia" />
                                    <flux:error name="textForm.title" />

                                    <div wire:ignore>
                                        <div x-data="markdownEditor('textForm.body')">
                                            <textarea x-ref="textarea" placeholder="Princípio + comentários..."></textarea>
                                        </div>
                                    </div>
                                    <flux:error name="textForm.body" />

                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:button type="button" variant="ghost" wire:click="cancelEditingText">Cancelar</flux:button>
                                        <flux:button type="submit" variant="primary">Salvar</flux:button>
                                    </div>
                                </form>
                            </x-timeline.content>
                        @else
                            <x-timeline.content class="group">
                                <div class="flex items-start gap-3 rounded-xl border border-surface-variant bg-surface-container-lowest p-6">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            @if ($principle->type === \App\Enums\PrincipleType::Concept)
                                                <flux:badge size="sm">Conceito</flux:badge>
                                            @endif

                                            @if ($principle->noteLinks->isNotEmpty())
                                                <x-info-popover>
                                                    <p class="mb-2 font-semibold">Aplicado em:</p>
                                                    <ul class="space-y-2">
                                                        @foreach ($principle->noteLinks as $link)
                                                            <li>
                                                                <a href="{{ route('disciplinas.show', ['slug' => $link->note->discipline->slug, 'nota' => $link->note_id]) }}" wire:navigate class="block text-primary hover:underline">
                                                                    {{ $link->note->title }}
                                                                </a>
                                                                <p class="text-xs text-on-surface-variant whitespace-pre-wrap">“{{ $link->snippet }}”</p>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </x-info-popover>
                                            @endif
                                        </div>

                                        @if ($principle->type === \App\Enums\PrincipleType::Concept)
                                            <flux:heading size="lg" class="mt-2">{{ $principle->concept->term }}</flux:heading>
                                            <p class="mt-2 text-on-surface-variant leading-relaxed whitespace-pre-wrap">{{ $principle->concept->definition }}</p>
                                        @else
                                            <flux:heading size="lg" class="mt-2">{{ $principle->title }}</flux:heading>
                                            <div class="prose dark:prose-invert max-w-none mt-2 leading-relaxed">
                                                {!! Str::markdownRich($principle->body) !!}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex shrink-0 flex-col gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <flux:button size="xs" variant="ghost" square icon="chevron-up"
                                            wire:click="movePrinciple({{ $principle->id }}, {{ $loop->index - 1 }})" :disabled="$loop->first" aria-label="Mover para cima" />
                                        <flux:button size="xs" variant="ghost" square icon="chevron-down"
                                            wire:click="movePrinciple({{ $principle->id }}, {{ $loop->index + 1 }})" :disabled="$loop->last" aria-label="Mover para baixo" />
                                        @if ($principle->type === \App\Enums\PrincipleType::Text)
                                            <flux:button size="xs" variant="ghost" square icon="pencil"
                                                wire:click="startEditingText({{ $principle->id }})" aria-label="Editar" />
                                        @endif
                                        <flux:button size="xs" variant="ghost" square icon="trash"
                                            wire:click="confirmDeletePrinciple({{ $principle->id }})" aria-label="Remover" />
                                    </div>
                                </div>
                            </x-timeline.content>
                        @endif
                    </x-timeline.item>
                @endforeach
            </x-timeline>
        @endif

        <flux:modal name="add-concept-principle" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">Adicionar conceito</flux:heading>

                <flux:input
                    wire:model.live.debounce.400ms="conceptSearch"
                    icon="magnifying-glass"
                    placeholder="Buscar conceito..."
                    clearable
                />

                @if ($this->selectedConcept)
                    <div class="rounded-lg border border-surface-variant bg-surface-container-low p-4">
                        <flux:heading size="md">{{ $this->selectedConcept->term }}</flux:heading>
                        <p class="mt-2 text-sm text-on-surface-variant whitespace-pre-wrap">{{ $this->selectedConcept->definition }}</p>
                    </div>

                    <div class="flex">
                        <flux:spacer />
                        <flux:button variant="primary" wire:click="addConcept">Adicionar</flux:button>
                    </div>
                @elseif (filled($conceptSearch))
                    <div class="divide-y divide-surface-variant overflow-hidden rounded-lg border border-surface-variant">
                        @forelse ($this->conceptResults as $result)
                            <button
                                type="button"
                                wire:key="concept-result-{{ $result->id }}"
                                wire:click="selectConcept({{ $result->id }})"
                                class="flex w-full items-center justify-between p-3 text-left text-sm text-on-surface hover:bg-surface-container-low"
                            >
                                {{ $result->term }}
                                <flux:icon name="chevron-right" class="size-4 text-on-surface-variant" />
                            </button>
                        @empty
                            <div class="p-3 text-sm text-on-surface-variant">Nenhum conceito encontrado.</div>
                        @endforelse
                    </div>
                @endif
            </div>
        </flux:modal>

        <flux:modal name="delete-principle" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Remover</flux:heading>
                    <flux:text class="mt-2">Esta ação não pode ser desfeita.</flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" icon="trash" wire:click="deletePrinciple">Remover</flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</div>
