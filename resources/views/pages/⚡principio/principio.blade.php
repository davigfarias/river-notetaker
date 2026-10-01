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
            <div class="flex items-center gap-3">
                <flux:heading size="xl" level="1">{{ $this->topic->title }}</flux:heading>
                <flux:modal.trigger name="add-category">
                    <flux:button size="sm" variant="ghost" icon="folder-plus" aria-label="Nova categoria" />
                </flux:modal.trigger>
            </div>

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
                    <flux:icon name="light-bulb" class="size-6 text-on-surface" />
                    Conceito
                </button>
            </flux:modal.trigger>

            <button
                type="button"
                wire:click="startAddingText"
                class="flex w-40 flex-col items-center justify-center gap-2 rounded-xl border border-surface-variant bg-surface-container-low p-6 text-center font-semibold hover:bg-surface-variant/40 transition-colors"
            >
                <flux:icon name="scale" class="size-6 text-on-surface" />
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

        @php
            $topic = $this->topic;
            $uncategorized = $topic->principles->whereNull('principle_category_id');
        @endphp

        @if ($topic->principles->isEmpty() && $topic->categories->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 px-6 text-center rounded-xl border border-dashed border-surface-variant bg-surface-container-low">
                <flux:icon name="scale" class="size-9 text-on-surface mb-3" />
                <flux:text class="text-surface-variant-content">Nenhum princípio adicionado a este tema ainda.</flux:text>
            </div>
        @else
            <x-timeline align="start" wire:sort="movePrinciple" wire:sort:group="principles" wire:sort:group-id="" class="mb-8">
                @if ($uncategorized->isEmpty())
                    <p wire:sort:ignore class="col-span-full rounded-xl border border-dashed border-surface-variant p-3 text-center text-sm text-on-surface-variant">Solte aqui para tirar da categoria</p>
                @endif
                @foreach ($uncategorized as $principle)
                    <x-principle-item
                        :principle="$principle"
                        :editing="$editingPrincipleId === $principle->id"
                        wire:key="principle-{{ $principle->id }}"
                        wire:sort:item="{{ $principle->id }}"
                    />
                @endforeach
            </x-timeline>

            @foreach ($topic->categories as $category)
                @php($categoryPrinciples = $topic->principles->where('principle_category_id', $category->id))
                <details wire:key="category-{{ $category->id }}" wire:ignore.self open class="group mb-8">
                    <summary class="flex cursor-pointer items-center gap-3 rounded-xl border border-surface-variant bg-surface-container-lowest p-4">
                        <flux:icon name="chevron-right" class="size-4 shrink-0 transition-transform group-open:rotate-90" />
                        <span class="font-medium">{{ $category->title }}</span>
                        <flux:badge size="sm">{{ $categoryPrinciples->count() }}</flux:badge>
                        <flux:spacer />
                        <flux:button size="xs" variant="ghost" icon="trash" wire:click.stop="confirmDeleteCategory({{ $category->id }})" aria-label="Remover categoria" />
                    </summary>

                    <div class="ml-6 mt-6 pl-6">
                        <x-timeline align="start" wire:sort="movePrinciple" wire:sort:group="principles" wire:sort:group-id="{{ $category->id }}">
                            @if ($categoryPrinciples->isEmpty())
                                <p wire:sort:ignore class="col-span-full rounded-xl border border-dashed border-surface-variant p-3 text-center text-sm text-on-surface-variant">Arraste princípios para cá</p>
                            @endif
                            @foreach ($categoryPrinciples as $principle)
                                <x-principle-item
                                    :principle="$principle"
                                    :editing="$editingPrincipleId === $principle->id"
                                    wire:key="principle-{{ $principle->id }}"
                                    wire:sort:item="{{ $principle->id }}"
                                />
                            @endforeach
                        </x-timeline>
                    </div>
                </details>
            @endforeach
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
                                <flux:icon name="chevron-right" class="size-4 text-on-surface" />
                            </button>
                        @empty
                            <div class="p-3 text-sm text-on-surface-variant">Nenhum conceito encontrado.</div>
                        @endforelse
                    </div>
                @endif
            </div>
        </flux:modal>

        <flux:modal name="add-category" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <form wire:submit="createCategory" class="space-y-5">
                <flux:heading size="lg">Nova categoria</flux:heading>
                <flux:input label="Título" wire:model="categoryForm.title" placeholder="Ex: Ordo salutis" />
                <flux:error name="categoryForm.title" />
                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Criar</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="delete-category" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Remover categoria</flux:heading>
                    <flux:text class="mt-2">Os princípios dentro dela voltam para a lista sem categoria.</flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" icon="trash" wire:click="deleteCategory">Remover</flux:button>
                </div>
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
