<x-slot:headerActions>
    <flux:button variant="ghost" icon="arrow-left" href="{{ route('referencias.show', $referenceMaterialId) }}" wire:navigate>
        {{ $referenceMaterialTitle }}
    </flux:button>
</x-slot:headerActions>

<main class="max-w-[1024px] mx-auto px-6 py-8 pb-32">
    @if ($this->question)
        <div class="flex items-center justify-between gap-2 mb-6">
            <span class="font-mono text-sm text-primary">Pergunta {{ $index + 1 }} de {{ $totalQuestions }}</span>

            <span class="flex items-center gap-1.5 font-mono text-sm text-on-surface-variant">
                <flux:icon.book-open class="size-3.5" />
                Modo revisão
            </span>
        </div>

        <div class="w-full bg-surface-variant rounded-full h-1.5 mb-10 overflow-hidden">
            <div class="bg-primary h-1.5 rounded-full transition-all" style="width: {{ $this->progress }}%"></div>
        </div>

        <div class="flex flex-col gap-6" wire:key="review-{{ $this->question->id }}">
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

                    <div class="my-6 border-t border-outline-variant"></div>

                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-primary/10 text-primary p-2 rounded-lg inline-flex">
                            <flux:icon.check-circle class="size-5" />
                        </div>
                        <span class="font-mono text-sm tracking-wider uppercase text-primary">Resposta</span>
                    </div>
                    <p class="font-sans text-base leading-relaxed whitespace-pre-line text-on-surface-variant">
                        {{ $this->question->reference_answer }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-outline-variant pt-6">
                <flux:button type="button" variant="ghost" icon="chevron-left" wire:click="previous" :disabled="$index === 0">
                    Voltar
                </flux:button>

                <flux:button type="button" variant="primary" icon:trailing="chevron-right" wire:click="next" :disabled="$index >= $totalQuestions - 1">
                    Avançar
                </flux:button>
            </div>
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
