<x-slot:headerActions>
    <flux:button variant="ghost" icon="arrow-left" href="{{ route('referencias.show', $referenceMaterialId) }}" wire:navigate>
        {{ $referenceMaterialTitle }}
    </flux:button>
</x-slot:headerActions>

<main class="max-w-[1024px] mx-auto px-6 py-8 pb-32">
    @if ($this->question)
        <div class="flex items-center justify-between gap-2 mb-6">
            <span class="font-mono text-sm text-primary">Pergunta {{ $index + 1 }} de {{ $totalQuestions }}</span>

            <div wire:key="timer-{{ $sessionRun }}" x-data="{
                seconds: 0,
                intervalId: null,
                init() {
                    this.intervalId = setInterval(() => this.seconds++, 1000);
                },
                destroy() {
                    clearInterval(this.intervalId);
                },
                formatted() {
                    const m = String(Math.floor(this.seconds / 60)).padStart(2, '0');
                    const s = String(this.seconds % 60).padStart(2, '0');
                    return m + ':' + s;
                },
            }" class="flex items-center gap-1.5 font-mono text-sm text-on-surface-variant">
                <flux:icon.clock class="size-3.5" />
                <span x-text="formatted()">00:00</span>
            </div>
        </div>

        <div class="w-full bg-surface-variant rounded-full h-1.5 mb-10 overflow-hidden">
            <div class="bg-primary h-1.5 rounded-full transition-all" style="width: {{ $this->progress }}%"></div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8 items-start">
            <div @class(['w-full flex flex-col gap-6', 'lg:w-2/3' => ! $this->isClozeQuestion])>
                <div class="bg-surface-container-high rounded-xl p-8 relative overflow-hidden border border-outline-variant shadow-lg">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-secondary"></div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-secondary/10 text-secondary p-2 rounded-lg inline-flex">
                                <flux:icon.light-bulb class="size-5" />
                            </div>
                            <span class="font-mono text-sm tracking-wider uppercase text-secondary">Pergunta</span>
                        </div>
                        <flux:heading size="lg" class="leading-tight">
                            {{ $this->question->prompt }}
                        </flux:heading>
                    </div>
                </div>

                @if ($this->isClozeQuestion)
                    <form wire:submit="submit" class="flex flex-col gap-3">
                        <label class="font-mono text-sm text-on-surface-variant">Complete as lacunas</label>
                        <div class="bg-surface-container-high rounded-xl p-6 border border-outline-variant">
                            <p class="font-sans text-base leading-loose text-on-surface">@foreach ($this->clozeSegments as $seg)@if ($seg['blank'])<input type="text" wire:model="clozeInputs.{{ $seg['index'] }}" class="cloze-blank" autocomplete="off" autocapitalize="off" spellcheck="false" />@else{{ $seg['text'] }}@endif@endforeach</p>
                        </div>

                        <div class="flex items-center justify-between mt-4 border-t border-outline-variant pt-6">
                            <flux:button type="button" variant="ghost" icon="forward" wire:click="skip">
                                Pular por agora
                            </flux:button>

                            <flux:button type="submit" variant="primary" icon:trailing="paper-airplane">
                                Enviar resposta
                            </flux:button>
                        </div>
                    </form>
                @else
                    <form wire:submit="submit" class="flex flex-col gap-3">
                        <label class="font-mono text-sm text-on-surface-variant" for="answer-input">Sua resposta</label>
                        <flux:textarea wire:model="answer" id="answer-input" rows="10" placeholder="Comece a digitar sua resposta aqui..." />
                        <flux:error name="answer" />

                        <div class="flex items-center justify-between mt-4 border-t border-outline-variant pt-6">
                            <flux:button type="button" variant="ghost" icon="forward" wire:click="skip">
                                Pular por agora
                            </flux:button>

                            <flux:button type="submit" variant="primary" icon:trailing="paper-airplane">
                                Enviar resposta
                            </flux:button>
                        </div>
                    </form>
                @endif
            </div>

            @unless ($this->isClozeQuestion)
            <aside class="w-full lg:w-1/3">
                <div class="bg-surface rounded-xl p-5 border border-outline-variant">
                    <h3 class="font-mono text-sm text-on-surface flex items-center gap-2 mb-3 border-b border-outline-variant pb-2">
                        <flux:icon.sparkles class="size-4 text-secondary" />
                        Ajuda de estudo
                    </h3>

                    @if ($hintLevel === 0)
                        <button type="button" wire:click="revealHint" class="w-full text-left flex items-center justify-between p-3 rounded-lg hover:bg-surface-variant/50 transition-colors group">
                            <span class="text-sm text-on-surface-variant group-hover:text-on-surface">Pedir uma dica</span>
                            <flux:icon.question-mark-circle class="size-4 text-on-surface-variant/50 group-hover:text-primary" />
                        </button>
                    @elseif ($this->hintPreview() === [])
                        <p class="font-sans text-sm text-on-surface-variant p-3 rounded-lg bg-surface-container-low">Nenhuma palavra-chave cadastrada para esta pergunta.</p>
                    @else
                        <div class="flex flex-wrap gap-2 p-3 rounded-lg bg-surface-container-low">
                            @foreach ($this->hintPreview() as $keyword)
                                <span class="font-mono text-sm px-3 py-1 rounded-full bg-secondary/15 text-secondary border border-secondary/30">{{ $keyword }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if ($hintLevel > 0 && $hintLevel < 3)
                        <button type="button" wire:click="revealHint" class="w-full text-left flex items-center justify-between p-3 rounded-lg hover:bg-surface-variant/50 transition-colors group mt-1">
                            <span class="text-sm text-on-surface-variant group-hover:text-on-surface">Revelar mais</span>
                            <flux:icon.eye class="size-4 text-on-surface-variant/50 group-hover:text-primary" />
                        </button>
                    @endif
                </div>
            </aside>
            @endunless
        </div>
    @else
        <div class="text-center flex flex-col items-center gap-4 py-16">
            <div class="w-16 h-16 rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center">
                <flux:icon.inbox class="size-8" />
            </div>
            <flux:heading size="lg">Este capítulo ainda não tem perguntas</flux:heading>
            <flux:button :href="route('referencias.show', $referenceMaterialId)" wire:navigate variant="primary">
                Voltar à obra
            </flux:button>
        </div>
    @endif
</main>
