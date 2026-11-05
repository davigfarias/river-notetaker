<div class="w-full space-y-8 py-8">
    {{-- Cabeçalho --}}
    <div class="space-y-3">
        <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('concepts')" wire:navigate>Voltar para Conceitos</flux:button>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <flux:heading size="xl" level="1" class="truncate">{{ $this->deck->name }}</flux:heading>
                <flux:text class="mt-2">
                    {{ $this->totalCards }} {{ $this->totalCards === 1 ? 'conceito' : 'conceitos' }} neste deck
                    @if ($this->inactiveCount > 0)
                        &middot; {{ $this->inactiveCount }} {{ $this->inactiveCount === 1 ? 'desativado' : 'desativados' }}
                    @endif
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:modal.trigger name="rename-deck">
                    <flux:button size="sm" variant="ghost" icon="pencil-square">Renomear</flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="delete-deck">
                    <flux:button size="sm" variant="ghost" icon="trash">Apagar deck</flux:button>
                </flux:modal.trigger>
                @if ($this->totalCards - $this->inactiveCount > 0)
                    <flux:button size="sm" variant="primary" icon="academic-cap" :href="route('concepts.decks.study', $this->deckId)" wire:navigate>Estudar</flux:button>
                @else
                    <flux:tooltip content="Este deck não tem cartões ativos">
                        <span><flux:button size="sm" variant="primary" icon="academic-cap" disabled>Estudar</flux:button></span>
                    </flux:tooltip>
                @endif
            </div>
        </div>
    </div>

    {{-- Cartões do deck --}}
    <section class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg" level="2">Conceitos do deck</flux:heading>
            @if ($this->inactiveCount > 0 || $showInactive)
                <flux:switch wire:model.live="showInactive" label="Mostrar desativados" />
            @endif
        </div>

        @if ($this->cards->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                <flux:button size="sm" variant="ghost" wire:click="selectAllCards">Selecionar todos</flux:button>
                @if (count($selectedCardIds) > 0)
                    <flux:text size="sm" class="text-on-surface-variant">{{ count($selectedCardIds) }} selecionado(s)</flux:text>
                    <flux:modal.trigger name="remove-cards">
                        <flux:button size="sm" icon="x-mark">Tirar do deck</flux:button>
                    </flux:modal.trigger>
                    <flux:modal.trigger name="deactivate-cards">
                        <flux:button size="sm" icon="eye-slash">Desativar</flux:button>
                    </flux:modal.trigger>
                    @if ($showInactive)
                        <flux:button size="sm" icon="eye" wire:click="reactivateSelectedCards">Reativar</flux:button>
                    @endif
                    <flux:button size="sm" variant="ghost" wire:click="$set('selectedCardIds', [])">Limpar seleção</flux:button>
                @endif
            </div>

            <div class="divide-y divide-surface-variant rounded-xl border border-surface-variant bg-surface-container-lowest">
                @foreach ($this->cards as $card)
                    <div wire:key="card-{{ $card->id }}" class="flex items-start gap-3 p-3 {{ $card->is_active ? '' : 'opacity-60' }}">
                        <flux:checkbox wire:model.live="selectedCardIds" value="{{ $card->id }}" aria-label="Selecionar {{ $card->concept->term }}" class="mt-1" />

                        <div class="min-w-0 flex-1">
                            <flux:heading class="flex flex-wrap items-center gap-2">
                                {{ $card->concept->term }}
                                @unless ($card->is_active)
                                    <flux:badge size="sm" color="zinc">Desativado</flux:badge>
                                @endunless
                            </flux:heading>
                            <flux:text size="sm" class="mt-1 line-clamp-2 text-on-surface-variant">{{ $card->concept->definition }}</flux:text>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            @if ($card->is_starred)
                                <flux:button size="sm" variant="ghost" icon="star" icon:variant="solid" wire:click="toggleStar({{ $card->id }})" aria-label="Tirar a estrela" class="text-amber-500!" />
                            @else
                                <flux:button size="sm" variant="ghost" icon="star" wire:click="toggleStar({{ $card->id }})" aria-label="Marcar com estrela" />
                            @endif
                            <flux:switch :checked="$card->is_active" wire:click="toggleActive({{ $card->id }})" aria-label="Cartão ativo no estudo" />
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($this->totalCards === 0)
            <x-empty-state icon="rectangle-stack" heading="Este deck ainda não tem conceitos" description="Use a busca abaixo para escolher conceitos do Dicionário.">
                <flux:button variant="primary" icon="plus" x-on:click="document.getElementById('escolher-conceitos')?.scrollIntoView({ behavior: 'smooth' })">Adicionar conceitos</flux:button>
            </x-empty-state>
        @else
            <x-empty-state icon="eye-slash" heading="Todos os cartões estão desativados" description="Ligue &quot;Mostrar desativados&quot; para ver e reativar os cartões.">
                <flux:button wire:click="$set('showInactive', true)">Mostrar desativados</flux:button>
            </x-empty-state>
        @endif
    </section>

    {{-- Seletor de conceitos --}}
    <section id="escolher-conceitos" class="space-y-4 rounded-xl border border-surface-variant bg-surface-container-low p-4">
        <div>
            <flux:heading size="lg" level="2">Adicionar conceitos do Dicionário</flux:heading>
            <flux:text size="sm" class="mt-1 text-on-surface-variant">Busque pelo nome ou escolha uma letra, marque os conceitos e clique em Adicionar.</flux:text>
        </div>

        @if (! $this->dictionaryHasConcepts)
            <x-empty-state icon="book-open" heading="O Dicionário ainda não tem conceitos.">
                <flux:button :href="route('concepts', ['aba' => 'dicionario'])" wire:navigate>Ir para o Dicionário</flux:button>
            </x-empty-state>
        @else
            <flux:input wire:model.live.debounce.300ms="pickerSearch" icon="magnifying-glass" placeholder="Buscar conceito..." maxlength="100" clearable />

            <div class="flex flex-wrap gap-1">
                @foreach ($this->alphabet as $letter)
                    <flux:button size="xs" :variant="$pickerLetter === $letter ? 'primary' : 'ghost'" wire:click="chooseLetter('{{ $letter }}')" wire:key="letra-{{ $letter }}">{{ $letter }}</flux:button>
                @endforeach
            </div>

            @if (! $this->pickerIsActive)
                <flux:text class="py-4 text-center text-on-surface-variant">Digite um nome na busca ou escolha uma letra para ver conceitos.</flux:text>
            @elseif ($this->pickerResults->isEmpty())
                <flux:text class="py-4 text-center text-on-surface-variant">
                    @if (filled(trim($pickerSearch)))
                        Nenhum conceito encontrado para '{{ trim($pickerSearch) }}'.
                    @else
                        Nenhum conceito começa com a letra {{ $pickerLetter }}.
                    @endif
                </flux:text>
            @else
                <div class="flex flex-wrap items-center gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="selectAllListed">Selecionar todos desta lista</flux:button>
                    <flux:button size="sm" variant="primary" icon="plus" wire:click="addSelectedConcepts" :disabled="count($selectedConceptIds) === 0">
                        Adicionar {{ count($selectedConceptIds) }} ao deck
                    </flux:button>
                </div>

                <div class="divide-y divide-surface-variant rounded-xl border border-surface-variant bg-surface-container-lowest">
                    @foreach ($this->pickerResults->take(300) as $concept)
                        @php($already = in_array($concept->id, $this->deckConceptIds, true))
                        <label wire:key="pick-{{ $concept->id }}" class="flex items-start gap-3 p-3 {{ $already ? 'opacity-60' : 'cursor-pointer' }}">
                            @if ($already)
                                <flux:checkbox checked disabled class="mt-1" />
                            @else
                                <flux:checkbox wire:model.live="selectedConceptIds" value="{{ $concept->id }}" class="mt-1" />
                            @endif
                            <div class="min-w-0 flex-1">
                                <flux:heading class="flex flex-wrap items-center gap-2">
                                    {{ $concept->term }}
                                    @if ($already)
                                        <flux:badge size="sm" color="green">Já está no deck</flux:badge>
                                    @endif
                                </flux:heading>
                                <flux:text size="sm" class="mt-1 line-clamp-2 text-on-surface-variant">{{ $concept->definition }}</flux:text>
                            </div>
                        </label>
                    @endforeach
                </div>

                @if ($this->pickerResults->count() > 300)
                    <flux:text size="sm" class="text-center text-on-surface-variant">Mostrando 300 de {{ $this->pickerResults->count() }}. Refine a busca para ver os outros.</flux:text>
                @endif
            @endif
        @endif
    </section>

    {{-- Modais --}}
    <flux:modal name="rename-deck" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
        <form wire:submit="renameDeck" class="space-y-5">
            <flux:heading size="lg">Renomear deck</flux:heading>
            <flux:input label="Nome do deck" wire:model="renameForm.name" maxlength="60" />
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-deck" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Apagar o deck '{{ $this->deck->name }}'?</flux:heading>
                <flux:text class="mt-2">Os conceitos continuam no Dicionário e o seu histórico de estudo é mantido. Só este deck e a lista de cartões dele somem. Isso não pode ser desfeito.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="deleteDeck">Apagar deck</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="remove-cards" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Tirar do deck?</flux:heading>
                <flux:text class="mt-2">Tirar {{ count($selectedCardIds) }} {{ count($selectedCardIds) === 1 ? 'conceito' : 'conceitos' }} deste deck? Eles continuam no Dicionário e o histórico de estudo é mantido.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="removeSelectedCards">Tirar do deck</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="deactivate-cards" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Desativar cartões?</flux:heading>
                <flux:text class="mt-2">Desativar {{ count($selectedCardIds) }} {{ count($selectedCardIds) === 1 ? 'cartão' : 'cartões' }}? Eles ficam no deck, mas não aparecem no estudo até você reativar.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="deactivateSelectedCards">Desativar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
