@props(['results'])

@php
    $hits = collect($results)->where('correct', true);
    $misses = collect($results)->where('correct', false);
@endphp

{{-- Dois cartões: acertos (verde, com caixa de active recall que não persiste) e erros (vermelho). --}}
<div class="space-y-3">
    <section class="space-y-3 rounded-lg border border-green-600/30 bg-green-500/10 p-4">
        <flux:heading size="sm" class="text-green-700 dark:text-green-400">
            Acertos ({{ $hits->count() }})
        </flux:heading>

        @forelse ($hits as $result)
            <div wire:key="quiz-hit-{{ $loop->index }}" class="space-y-1">
                <flux:text class="font-medium">{{ $result['question'] }}</flux:text>
                <flux:text size="sm" class="text-green-700 dark:text-green-400">{{ $result['chosen'] }}</flux:text>

                {{-- wire:ignore: o que for digitado fica só no navegador e não some se a modal re-renderizar. --}}
                <div wire:ignore>
                    <flux:textarea
                        rows="2"
                        maxlength="1000"
                        label="Por que essa é a resposta correta?"
                        placeholder="Escreva com as suas palavras antes de seguir. Não fica salvo."
                    />
                </div>
            </div>
        @empty
            <flux:text size="sm" class="text-on-surface-variant">Nenhuma resposta correta desta vez.</flux:text>
        @endforelse
    </section>

    <section class="space-y-3 rounded-lg border border-red-600/30 bg-red-500/10 p-4">
        <flux:heading size="sm" class="text-red-700 dark:text-red-400">
            Erros ({{ $misses->count() }})
        </flux:heading>

        @forelse ($misses as $result)
            <div wire:key="quiz-miss-{{ $loop->index }}" class="space-y-1">
                <flux:text class="font-medium">{{ $result['question'] }}</flux:text>
                <flux:text size="sm" class="text-red-700 dark:text-red-400">
                    <span class="line-through">{{ $result['chosen'] !== '' ? $result['chosen'] : '—' }}</span>
                </flux:text>
                <flux:text size="sm" class="text-on-surface font-medium">{{ $result['correct_answer'] }}</flux:text>
            </div>
        @empty
            <flux:text size="sm" class="text-on-surface-variant">Nenhum erro. Você acertou tudo.</flux:text>
        @endforelse
    </section>
</div>
