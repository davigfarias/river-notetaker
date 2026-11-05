@php($stats = $this->stats)

<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl" level="2">Decks de conceitos</flux:heading>
            <flux:text class="mt-2">
                @if ($stats->toReinforce > 0)
                    Você tem {{ $stats->toReinforce }} {{ $stats->toReinforce === 1 ? 'conceito' : 'conceitos' }} para reforçar.
                @else
                    Nenhum conceito precisa de reforço agora.
                @endif
            </flux:text>
        </div>

        <flux:modal.trigger name="new-concept-deck">
            <flux:button variant="primary" icon="plus">Novo deck</flux:button>
        </flux:modal.trigger>
    </div>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-surface-variant bg-surface-container-lowest p-5 shadow-sm">
            <flux:text size="sm" class="text-on-surface-variant">Acerto de hoje</flux:text>
            @if ($stats->rememberedTodayPercent === null)
                <p class="mt-2 text-3xl font-semibold text-on-surface">—</p>
                <flux:text size="sm" class="mt-1 text-on-surface-variant">Nenhum cartão estudado hoje</flux:text>
            @else
                <p class="mt-2 text-3xl font-semibold text-on-surface">{{ $stats->rememberedTodayPercent }}%</p>
                <flux:progress :value="$stats->rememberedTodayPercent" class="mt-2" />
                <flux:text size="sm" class="mt-1 text-on-surface-variant">
                    {{ $stats->reviewedToday }} {{ $stats->reviewedToday === 1 ? 'cartão estudado' : 'cartões estudados' }} hoje
                </flux:text>
            @endif
        </div>

        <div class="rounded-xl border border-surface-variant bg-surface-container-lowest p-5 shadow-sm">
            <flux:text size="sm" class="text-on-surface-variant">Sequência de estudo</flux:text>
            <p class="mt-2 text-3xl font-semibold text-on-surface">
                {{ $stats->streak->days }}
                <span class="text-base font-normal text-on-surface-variant">{{ $stats->streak->days === 1 ? 'dia' : 'dias' }}</span>
            </p>
            <div class="mt-2 flex gap-1" aria-label="Últimos 7 dias">
                @foreach ($stats->streak->last7 as $studied)
                    <div class="h-6 flex-1 rounded-full {{ $studied ? 'bg-primary' : 'bg-surface-variant' }}"></div>
                @endforeach
            </div>
            @if ($stats->streak->days === 0)
                <flux:text size="sm" class="mt-1 text-on-surface-variant">Estude um cartão para começar a sequência.</flux:text>
            @endif
        </div>

        <div class="rounded-xl border border-surface-variant bg-surface-container-lowest p-5 shadow-sm">
            <flux:text size="sm" class="text-on-surface-variant">A reforçar</flux:text>
            <p class="mt-2 text-3xl font-semibold text-on-surface">{{ $stats->toReinforce }}</p>
            <flux:text size="sm" class="mt-1 text-on-surface-variant">
                {{ $stats->toReinforce === 0 ? 'Tudo em dia.' : 'Conceitos que você errou mais do que acertou.' }}
            </flux:text>
        </div>
    </section>

    <section class="rounded-xl border border-surface-variant bg-surface-container-lowest p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <flux:heading size="lg" level="3">Atividade de estudo</flux:heading>
            <flux:select wire:model.live="range" size="sm" class="w-44" aria-label="Período da atividade">
                <flux:select.option value="7d">Últimos 7 dias</flux:select.option>
                <flux:select.option value="30d">Últimos 30 dias</flux:select.option>
                <flux:select.option value="all">Desde o início</flux:select.option>
            </flux:select>
        </div>

        @if (array_sum(array_column($stats->activity, 'count')) === 0)
            <flux:text class="py-10 text-center text-on-surface-variant">Nenhum cartão estudado neste período.</flux:text>
        @else
            <div class="flex h-40 items-end gap-2 border-b border-surface-variant pb-1">
                @foreach ($stats->activity as $bucket)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-1" wire:key="atividade-{{ $range }}-{{ $loop->index }}" title="{{ $bucket['count'] }} {{ $bucket['count'] === 1 ? 'revisão' : 'revisões' }}">
                        <span class="text-[10px] text-on-surface-variant">{{ $bucket['count'] }}</span>
                        <div class="w-full rounded-t-sm bg-primary-container" style="height: {{ max($bucket['percent'], 2) }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-1 flex gap-2">
                @foreach ($stats->activity as $bucket)
                    <span class="flex-1 text-center text-[10px] text-on-surface-variant">{{ $bucket['label'] }}</span>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <flux:heading size="lg" level="3" class="mb-3">Seus decks</flux:heading>

        @forelse ($stats->decks as $deck)
            <div wire:key="deck-{{ $deck['id'] }}" class="mb-3 flex flex-col gap-3 rounded-xl border border-surface-variant bg-surface-container-lowest p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between {{ $deck['dim'] ? 'opacity-90' : '' }}">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        @if ($deck['urgent'])
                            <span class="size-2 shrink-0 rounded-full bg-error" title="Muitos conceitos para reforçar"></span>
                        @endif
                        <flux:heading class="truncate">{{ $deck['name'] }}</flux:heading>
                    </div>
                    <flux:text size="sm" class="mt-1 text-on-surface-variant">
                        {{ $deck['cardsCount'] }} {{ $deck['cardsCount'] === 1 ? 'conceito' : 'conceitos' }}
                        @if ($deck['activeCount'] !== $deck['cardsCount'])
                            ({{ $deck['activeCount'] }} {{ $deck['activeCount'] === 1 ? 'ativo' : 'ativos' }})
                        @endif
                        &middot; {{ $deck['meta'] }}
                    </flux:text>
                </div>

                <div class="flex shrink-0 gap-2">
                    <flux:button size="sm" variant="ghost" icon="folder-open" :href="route('concepts.decks.show', $deck['id'])" wire:navigate>Abrir</flux:button>
                    @if ($deck['activeCount'] > 0)
                        <flux:button size="sm" variant="primary" icon="academic-cap" :href="route('concepts.decks.study', $deck['id'])" wire:navigate>Estudar</flux:button>
                    @else
                        <flux:tooltip content="Este deck não tem cartões ativos">
                            <span><flux:button size="sm" variant="primary" icon="academic-cap" disabled>Estudar</flux:button></span>
                        </flux:tooltip>
                    @endif
                </div>
            </div>
        @empty
            <x-empty-state
                icon="rectangle-stack"
                heading="Você ainda não tem decks"
                description="Crie um deck e escolha conceitos do Dicionário para estudar com cartões."
            >
                <flux:modal.trigger name="new-concept-deck">
                    <flux:button variant="primary" icon="plus">Criar deck</flux:button>
                </flux:modal.trigger>
            </x-empty-state>
        @endforelse
    </section>

    <flux:modal name="new-concept-deck" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
        <form wire:submit="addDeck" class="space-y-5">
            <div>
                <flux:heading size="lg">Novo deck</flux:heading>
                <flux:text class="mt-2">Um conjunto de conceitos do Dicionário para estudar com cartões. O mesmo conceito pode estar em mais de um deck.</flux:text>
            </div>

            <flux:input label="Nome do deck" wire:model="deckForm.name" maxlength="60" placeholder="Ex: Soteriologia" />

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Criar deck</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
