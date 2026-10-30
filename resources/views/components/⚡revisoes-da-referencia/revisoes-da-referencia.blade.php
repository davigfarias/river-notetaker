<div @class(['mb-10' => $referenceMaterialId === null])>
    @php($agenda = $this->agenda)
    @php($isGlobal = $referenceMaterialId === null)

    <div class="mb-4 flex flex-wrap items-center gap-3">
        @if ($isGlobal)
            <flux:heading size="lg">Revisões de recursos</flux:heading>
        @else
            <flux:heading size="sm" class="text-on-surface-variant uppercase">Revisão espaçada</flux:heading>
        @endif
        <flux:badge size="sm" variant="pill" :color="$agenda->isEmpty() ? 'zinc' : 'amber'">
            {{ $agenda->totalDue }} pendente{{ $agenda->totalDue === 1 ? '' : 's' }} hoje
        </flux:badge>
    </div>

    @if (! $agenda->isEmpty())
        @if ($agenda->hiddenCount() > 0 || $showAllDue)
            <div class="mb-3 flex flex-wrap items-center gap-x-3">
                <flux:text size="sm" class="text-on-surface-variant">
                    @if ($showAllDue)
                        Mostrando todas as {{ $agenda->due->count() }}.
                    @else
                        Mostrando {{ $agenda->due->count() }}, mais {{ $agenda->hiddenCount() }} aguardando.
                    @endif
                </flux:text>
                <flux:button type="button" size="xs" variant="subtle" wire:click="toggleShowAllDue">
                    {{ $showAllDue ? 'Mostrar só as primeiras' : 'Ver todas ('.$agenda->totalDue.')' }}
                </flux:button>
            </div>
        @endif

        <div class="mb-6 grid gap-3 sm:grid-cols-2">
            @foreach ($agenda->due as $review)
                <div wire:key="due-reading-note-{{ $review->id }}" class="relative">
                    <button
                        type="button"
                        wire:click="openReview({{ $review->id }})"
                        class="block h-full w-full text-left"
                        aria-label="Revisar {{ $review->title }}"
                    >
                        <flux:card size="sm" class="hover:border-primary/50 flex h-full flex-col justify-between pe-12 transition-colors">
                            <div>
                                @if ($isGlobal && $review->reference_title)
                                    <flux:text size="sm" class="text-on-surface-variant mb-1 truncate">{{ $review->reference_title }}</flux:text>
                                @endif
                                <flux:heading size="sm" class="line-clamp-2">{{ $review->title }}</flux:heading>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <flux:badge size="sm" variant="pill">{{ $review->stage_label }}</flux:badge>

                                <flux:badge
                                    size="sm"
                                    variant="pill"
                                    :color="$review->isOverdue() ? 'red' : 'zinc'"
                                >
                                    {{ $review->overdueLabel() }}
                                </flux:badge>
                            </div>
                        </flux:card>
                    </button>

                    <div class="absolute top-2 right-2">
                        <flux:button
                            type="button"
                            size="xs"
                            variant="ghost"
                            icon="x-mark"
                            aria-label="Tirar da fila: {{ $review->title }}"
                            title="Tirar da fila"
                            wire:click="confirmDismiss({{ $review->id }})"
                        />
                    </div>
                </div>
            @endforeach
        </div>
    @elseif ($agenda->totalNotes === 0)
        <flux:card size="sm" class="mb-6 flex items-center gap-3">
            <flux:icon.pencil-square class="text-on-surface size-5 shrink-0" />
            <flux:text>
                {{ $isGlobal ? 'Você ainda não tem anotações de recursos.' : 'Esta obra ainda não tem anotações.' }}
                Quando você escrever uma, ela aparece aqui para revisar.
            </flux:text>
        </flux:card>
    @else
        <flux:card size="sm" class="mb-6 flex items-center gap-3">
            <flux:icon.check-circle class="text-on-surface size-5 shrink-0" />
            <flux:text>
                Nenhuma revisão pendente para hoje.
                @if ($agenda->nextDueAt)
                    A próxima vence em {{ \Carbon\CarbonImmutable::parse($agenda->nextDueAt)->format('d/m') }}.
                @else
                    Não há outras agendadas: o resto já foi consolidado ou tirado da fila.
                @endif
            </flux:text>
        </flux:card>
    @endif

    @if ($agenda->dismissedCount > 0)
        <div class="mb-6">
            <flux:button type="button" size="sm" variant="subtle" :icon="$showDismissed ? 'chevron-up' : 'chevron-down'" wire:click="$toggle('showDismissed')">
                Tiradas da fila ({{ $agenda->dismissedCount }})
            </flux:button>

            @if ($showDismissed)
                <div class="mt-3 space-y-2">
                    @foreach ($this->dismissed as $item)
                        <flux:card size="sm" wire:key="dismissed-reading-note-{{ $item->id }}" class="flex items-center gap-3">
                            <div class="min-w-0 flex-1">
                                @if ($isGlobal && $item->reference_title)
                                    <flux:text size="sm" class="text-on-surface-variant truncate">{{ $item->reference_title }}</flux:text>
                                @endif
                                <flux:text class="truncate font-medium">{{ $item->title }}</flux:text>
                            </div>
                            <flux:button type="button" size="sm" icon="arrow-uturn-left" wire:click="restore({{ $item->id }})">
                                Voltar para a fila
                            </flux:button>
                        </flux:card>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <flux:modal wire:model="showDismissConfirm" name="tirar-da-fila-{{ $referenceMaterialId ?? 'todas' }}" class="min-w-88">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Tirar da fila de revisão?</flux:heading>
                <flux:text class="mt-2">
                    @if ($this->noteAwaitingDismissConfirm)
                        “{{ $this->noteAwaitingDismissConfirm->title }}” não vai mais aparecer nas revisões.
                    @endif
                    A anotação não é apagada. Para desfazer, abra “Tiradas da fila” e toque em “Voltar para a fila”.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="cancelDismiss">Manter na fila</flux:button>
                <flux:button variant="danger" wire:click="dismiss">Tirar da fila</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal
        wire:model="showReviewModal"
        name="revisao-anotacao-{{ $referenceMaterialId ?? 'todas' }}"
        class="w-full max-w-[calc(100vw-2rem)] p-0 sm:max-w-2xl"
    >
        @if ($this->readingNoteUnderReview)
            @php($note = $this->readingNoteUnderReview)

            <div class="flex max-h-[85dvh] flex-col" wire:key="review-reading-note-{{ $note->id }}">
                <div class="border-outline-variant/50 shrink-0 border-b px-6 pt-6 pb-4">
                    <flux:heading size="lg" class="pe-10">{{ $note->displayTitle() }}</flux:heading>

                    @if ($note->location)
                        <flux:text size="sm" class="text-on-surface-variant mt-1">
                            {{ $note->location }}
                        </flux:text>
                    @endif
                </div>

                <div data-scroll-hint-host class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-5">
                    <x-scroll-hint />

                    @if ($note->tags)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($note->tags as $tag)
                                <flux:badge size="sm" variant="pill" wire:key="review-tag-{{ $loop->index }}">
                                    {{ $tag }}
                                </flux:badge>
                            @endforeach
                        </div>
                    @endif

                    @if ($quizScore === null)
                        {{-- Fase de resposta: perguntas de múltipla escolha geradas a
                             partir do título e do corpo da anotação. Responder é a prova
                             de recall; o placar é que decide a revisão, não a
                             autoavaliação. --}}
                        @if ($this->awaitingQuiz)
                            <section
                                class="border-surface-variant bg-primary-container/10 space-y-2 rounded-lg border p-4"
                                wire:poll.{{ config('quiz.poll_interval', '2s') }}="pollCheckQuiz"
                            >
                                <div class="flex items-center gap-2">
                                    <flux:icon name="sparkles" class="text-primary size-4 animate-pulse" />
                                    <flux:heading size="xs">Gerando as perguntas</flux:heading>
                                </div>
                                <div class="bg-surface-variant h-3 w-full animate-pulse rounded"></div>
                                <div class="bg-surface-variant h-3 w-3/4 animate-pulse rounded"></div>
                            </section>
                        @elseif ($quizQuestions)
                            <section class="space-y-5">
                                <flux:heading size="sm" class="text-on-surface-variant uppercase">Responda as perguntas</flux:heading>

                                @foreach ($quizQuestions as $question)
                                    <div wire:key="quiz-question-{{ $question['id'] }}" class="space-y-2">
                                        <flux:text class="font-medium">{{ $question['question'] }}</flux:text>

                                        <flux:radio.group wire:model.live="quizAnswers.{{ $question['id'] }}">
                                            @foreach ($question['options'] as $option)
                                                <flux:radio wire:key="quiz-option-{{ $question['id'] }}-{{ $loop->index }}" value="{{ $option }}" label="{{ $option }}" />
                                            @endforeach
                                        </flux:radio.group>
                                    </div>
                                @endforeach
                            </section>
                        @else
                            <div class="border-outline-variant/60 flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-10 text-center">
                                <flux:icon.exclamation-triangle class="text-on-surface size-6" />
                                <flux:text>Não foi possível gerar as perguntas desta revisão. Tente novamente mais tarde.</flux:text>
                            </div>
                        @endif
                    @else
                        {{-- Fase de resultado. --}}
                        <section class="space-y-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <flux:heading size="sm" class="text-on-surface-variant uppercase">Resultado</flux:heading>
                                <flux:badge size="sm" variant="pill" :color="$lastRecalled ? 'green' : 'amber'">
                                    {{ $quizScore }}%
                                </flux:badge>
                                <flux:text size="sm" class="text-on-surface-variant">
                                    {{ $lastRecalled ? 'Lembrou: a anotação sobe um degrau.' : 'A anotação volta para o primeiro degrau.' }}
                                </flux:text>
                            </div>

                            <div class="space-y-3">
                                @foreach ($quizResults as $result)
                                    <div wire:key="quiz-result-{{ $loop->index }}" class="space-y-1">
                                        <flux:text class="font-medium">{{ $result['question'] }}</flux:text>
                                        <flux:text size="sm" class="{{ $result['correct'] ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                            @if ($result['correct'])
                                                {{ $result['chosen'] }}
                                            @else
                                                <span class="line-through">{{ $result['chosen'] !== '' ? $result['chosen'] : '—' }}</span>
                                                <span class="font-medium text-on-surface">{{ $result['correct_answer'] }}</span>
                                            @endif
                                        </flux:text>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                <div class="border-outline-variant/50 shrink-0 border-t bg-transparent px-6 py-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button type="button" variant="ghost" wire:click="closeReview">
                            Parar por aqui
                        </flux:button>

                        <flux:spacer />

                        @if ($quizScore === null)
                            <flux:button type="button" variant="danger" icon="arrow-path" wire:click="giveUp">
                                Não lembro
                            </flux:button>

                            <flux:button
                                type="button"
                                variant="primary"
                                icon="check"
                                wire:click="submitQuiz"
                                :disabled="$quizQuestions === [] || count($quizAnswers) < count($quizQuestions)"
                            >
                                Conferir
                            </flux:button>
                        @else
                            <flux:button type="button" variant="primary" icon="arrow-right" wire:click="nextNote">
                                Próxima anotação
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
