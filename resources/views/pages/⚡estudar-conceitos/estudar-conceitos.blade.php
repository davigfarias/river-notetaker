<x-slot:headerActions>
    <flux:button variant="ghost" icon="arrow-left" href="{{ route('concepts.decks.show', $deckId) }}" wire:navigate>
        {{ $deckName }}
    </flux:button>
</x-slot:headerActions>

<main
    class="mx-auto flex w-full max-w-[800px] flex-col py-8 pb-32"
    x-data="{
        transitioning: false,
        submitAnswer(result) {
            if (this.transitioning) return;
            this.transitioning = true;
            $wire.answer(result).then(() => {
                setTimeout(() => {
                    $wire.advance().then(() => { this.transitioning = false; });
                }, 500);
            });
        },
    }"
    x-on:keydown.window="
        const tag = ($event.target.tagName || '').toLowerCase();
        if (['input', 'textarea', 'select'].includes(tag) || $event.target.isContentEditable) return;
        if (transitioning) return;
        if ($event.key === 'Backspace' && @js($this->canGoBack)) { $event.preventDefault(); $wire.goBack(); return; }
        if ($event.code === 'Space') { $event.preventDefault(); if (!@js($revealed)) $wire.reveal(); }
        if (@js($revealed) && ['1', '2'].includes($event.key)) {
            submitAnswer({ 1: 'forgot', 2: 'remembered' }[$event.key]);
        }
    "
>
    <div class="mb-6 flex items-center gap-3">
        <flux:button
            wire:click="goBack"
            x-bind:disabled="transitioning || !@js($this->canGoBack)"
            variant="ghost"
            size="sm"
            icon="arrow-uturn-left"
            title="Desfazer a resposta anterior"
            aria-label="Desfazer a resposta anterior"
        />
        <flux:progress :value="$this->progress" class="flex-1" />
        <span class="shrink-0 text-sm font-semibold text-on-surface-variant">{{ $this->progress }}%</span>
        @if ($this->card)
            @if ($this->card->is_starred)
                <flux:button wire:click="toggleStar" wire:key="star-{{ $this->card->id }}" size="sm" variant="ghost" icon="star" icon:variant="solid" class="text-amber-500!" title="Tirar a estrela" aria-label="Tirar a estrela" />
            @else
                <flux:button wire:click="toggleStar" wire:key="star-{{ $this->card->id }}" size="sm" variant="ghost" icon="star" title="Marcar com estrela" aria-label="Marcar com estrela" />
            @endif
        @endif
    </div>

    @if ($this->isCardMissing)
        <x-empty-state icon="exclamation-triangle" heading="Este conceito foi apagado ou saiu do deck." description="Esse cartão não pode mais ser estudado.">
            <flux:button wire:click="skipMissing" variant="primary">Pular</flux:button>
        </x-empty-state>
    @elseif ($this->card)
        <div class="mx-auto mb-8 h-[400px] w-full max-w-[600px] cursor-pointer [perspective:1000px] md:h-[480px]" x-bind:class="{ 'pointer-events-none': transitioning }" wire:click="reveal" wire:key="card-{{ $this->card->id }}-{{ $index }}">
            <div @class(['relative h-full w-full transform-3d transition-transform duration-500 ease-in-out', 'rotate-x-180' => $revealed])>
                <div class="absolute inset-0 flex flex-col items-center justify-center rounded-xl border border-t-4 border-outline-variant border-t-primary bg-surface-container-low p-8 shadow-lg backface-hidden">
                    <flux:heading size="xl" level="2" class="text-center">{{ $this->card->concept->term }}</flux:heading>
                    <flux:text class="mt-6 opacity-60">Toque para virar</flux:text>
                </div>

                <div class="absolute inset-0 flex rotate-x-180 flex-col rounded-xl border border-t-4 border-outline-variant border-t-secondary bg-surface-container-low p-6 shadow-lg backface-hidden">
                    <flux:heading size="lg" level="2" class="mb-3 shrink-0 text-center">{{ $this->card->concept->term }}</flux:heading>
                    <div class="min-h-0 flex-1 overflow-y-auto whitespace-pre-line text-on-surface">{{ $this->card->concept->definition }}</div>
                </div>
            </div>
        </div>

        @if (! $revealed)
            <div class="flex w-full max-w-[600px] justify-center self-center">
                <flux:button wire:click="reveal" x-bind:disabled="transitioning" variant="primary" icon:trailing="eye" class="w-full justify-center md:w-auto">
                    Mostrar resposta
                </flux:button>
            </div>
        @else
            <div class="grid w-full max-w-[600px] grid-cols-2 gap-3 self-center sm:gap-4">
                <flux:button x-on:click="submitAnswer('forgot')" x-bind:disabled="transitioning" icon="x-mark" class="justify-center">Não lembrei</flux:button>
                <flux:button x-on:click="submitAnswer('remembered')" x-bind:disabled="transitioning" variant="primary" icon="check" class="justify-center">Lembrei</flux:button>
            </div>
        @endif

        <div class="mt-8 hidden text-center text-sm text-on-surface-variant md:block">
            Pressione <kbd class="mx-1 rounded border border-outline-variant bg-surface-container px-2 py-1">Espaço</kbd> para virar,
            <kbd class="mx-1 rounded border border-outline-variant bg-surface-container px-2 py-1">1</kbd> não lembrei,
            <kbd class="mx-1 rounded border border-outline-variant bg-surface-container px-2 py-1">2</kbd> lembrei e
            <kbd class="mx-1 rounded border border-outline-variant bg-surface-container px-2 py-1">Backspace</kbd> desfazer.
        </div>
    @elseif ($this->endState === 'empty')
        <x-empty-state icon="rectangle-stack" heading="Este deck ainda não tem conceitos." description="Escolha conceitos do Dicionário para poder estudar.">
            <flux:button variant="primary" :href="route('concepts.decks.show', $deckId)" wire:navigate>Adicionar conceitos</flux:button>
        </x-empty-state>
    @elseif ($this->endState === 'inactive')
        <x-empty-state icon="eye-slash" heading="Todos os cartões deste deck estão desativados." description="Reative pelo menos um cartão na página do deck.">
            <flux:button variant="primary" :href="route('concepts.decks.show', $deckId)" wire:navigate>Abrir o deck</flux:button>
        </x-empty-state>
    @else
        <x-empty-state icon="check-badge" heading="Sessão concluída" :description="$totalCards === 1 ? 'Você revisou o único cartão deste treino.' : 'Você revisou todos os '.$totalCards.' cartões deste treino.'">
            <flux:button wire:click="restart" variant="primary" icon="arrow-path">Estudar novamente</flux:button>
        </x-empty-state>
    @endif
</main>
