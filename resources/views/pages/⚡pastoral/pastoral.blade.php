<x-slot:headerActions>
    <flux:button variant="primary" icon="plus" href="{{ route('notas.criar') }}" wire:navigate>
        Nova Nota
    </flux:button>
</x-slot:headerActions>

@placeholder
    <div>
        <div class="mx-auto w-full max-w-7xl py-8">
            <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <flux:heading size="xl" level="1">Conselhos Pastorais</flux:heading>
                    <flux:text class="mt-2">Orientações pastorais organizadas por tema.</flux:text>
                </div>
            </div>

            <div class="space-y-10">
                @foreach (range(1, 5) as $i)
                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line class="mb-2 w-1/4" />
                        <flux:skeleton.line />
                        <flux:skeleton.line />
                        <flux:skeleton.line class="w-3/4" />
                    </flux:skeleton.group>
                @endforeach
            </div>
        </div>
    </div>
@endplaceholder

<div>
    <div class="mx-auto w-full max-w-7xl py-8">

        <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">Conselhos Pastorais</flux:heading>
                <flux:text class="mt-2">Orientações pastorais organizadas por tema.</flux:text>
            </div>

            <div>
                <flux:modal.trigger name="add-advice">
                    <flux:button>Adicionar um novo conselho</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <div class="mb-8">
            <flux:input
                name="search"
                icon="magnifying-glass"
                wire:model.live.debounce.500ms="search"
                placeholder="Pesquisar por tema ou conselho..."
                clearable
                class="max-w-md"
            />
        </div>

        @if ($this->themes->isEmpty())
            <div class="flex flex-col items-center justify-center py-24 px-6 text-center rounded-xl border border-surface-variant bg-surface-container-low border-dashed">
                <flux:icon name="hand-raised" class="size-10 text-surface-variant-content/50 mb-3" />
                <flux:heading size="md">Nenhum conselho encontrado</flux:heading>
                <flux:text class="mt-2 text-surface-variant-content">
                    @if(!empty($search))
                        Nenhum conselho corresponde à busca "{{ $search }}".
                    @else
                        Ainda não existem conselhos pastorais cadastrados.
                    @endif
                </flux:text>
            </div>
        @else
            <div class="space-y-10">
                @foreach ($this->themes as $theme)
                    <div wire:key="theme-{{ \Illuminate\Support\Str::slug($theme['category']) }}">
                        <flux:heading size="xl" level="2">{{ $theme['category'] }}</flux:heading>

                        <flux:separator class="my-4" />

                        <div class="space-y-4">
                            @foreach ($theme['advices'] as $advice)
                                <div wire:key="advice-{{ $advice->id }}" class="group relative flex items-start gap-2">
                                    <p class="italic text-lg text-on-surface-variant leading-relaxed">
                                        &ldquo;{{ $advice->advice }}&rdquo;
                                    </p>

                                    <button
                                        type="button"
                                        wire:click="edit({{ $advice->id }})"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity text-on-surface-variant hover:text-primary shrink-0 mt-1"
                                    >
                                        <flux:icon name="pencil" class="size-4" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:pagination :paginator="$this->themes" class="mt-10" />
        @endif

        <flux:modal name="add-advice" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Adicionar um novo conselho</flux:heading>
                    <flux:text class="mt-2">Precisa de um conselho sem precisar de uma nota? Adicione diretamente aqui.</flux:text>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Tema principal</flux:label>

                        <x-lwa::autocomplete
                            name="category-autocomplete"
                            wire:model-text="formAdvice.category"
                            wire:model-id="categorySelectedId"
                            wire:model-results="categorySuggestions"
                            wire:focus="getCategorySuggestions"
                            placeholder="Ex: Casamento"
                            :options="['allow-new' => true, 'auto-select' => false]"
                        />
                    </flux:field>
                    @error('formAdvice.category') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div>
                    <flux:textarea
                        label="Conselho"
                        wire:model="formAdvice.advice"
                        placeholder="O conselho pastoral em si..."
                    />
                    @error('formAdvice.advice') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="flex">
                    <flux:spacer />
                    <flux:button
                        type="submit"
                        variant="primary"
                        wire:click="addSoleAdvice">Adicionar Conselho</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="edit-advice" wire:model.self="editingAdvice" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Editar conselho</flux:heading>
                </div>

                <flux:input
                    label="Tema principal"
                    wire:model="editAdviceForm.category"
                    placeholder="Ex: Casamento" />
                @error('editAdviceForm.category') <span class="error">{{ $message }}</span> @enderror

                <flux:textarea
                    label="Conselho"
                    wire:model="editAdviceForm.advice"
                    placeholder="O conselho pastoral em si..."
                />
                @error('editAdviceForm.advice') <span class="error">{{ $message }}</span> @enderror

                <div class="flex">
                    <flux:spacer />
                    <flux:button
                        type="submit"
                        variant="primary"
                        wire:click="updateAdvice">Salvar</flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</div>
