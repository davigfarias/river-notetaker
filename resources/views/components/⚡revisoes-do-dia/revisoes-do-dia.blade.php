<div class="mb-10">
    @php($agenda = $this->agenda)

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:heading size="lg">Revisões de hoje</flux:heading>
        <flux:badge size="sm" variant="pill" :color="$agenda->isEmpty() ? 'zinc' : 'amber'">
            {{ $agenda->totalDue }}
        </flux:badge>
        <flux:text size="sm" class="text-on-surface-variant">{{ $agenda->today->label() }}</flux:text>
    </div>

    @if ($agenda->hasNotesAwaitingSummary())
        <flux:card size="sm" class="mb-4 flex items-start gap-3">
            <flux:icon.pencil-square class="text-on-surface-variant mt-0.5 size-5 shrink-0" />
            <div>
                <flux:text class="font-medium">{{ $agenda->awaitingSummaryLabel() }}.</flux:text>
                <flux:text size="sm" class="text-on-surface-variant mt-1 block">
                    Elas não entram na revisão enquanto você não escrever o resumo.
                </flux:text>
            </div>
        </flux:card>
    @endif

    @if (! $agenda->isEmpty())
        <flux:text size="sm" class="text-on-surface-variant mb-3 block">
            {{ $agenda->disciplinesInQueue()->implode(' · ') }}
            @if ($agenda->hiddenCount() > 0)
                — mostrando {{ $agenda->due->count() }}, mais {{ $agenda->hiddenCount() }} aguardando
            @endif
        </flux:text>

        <div class="-mx-1 flex snap-x snap-mandatory gap-4 overflow-x-auto px-1 pb-3">
            @foreach ($agenda->due as $review)
                <button
                    type="button"
                    wire:key="due-review-{{ $review->id }}"
                    wire:click="openReview({{ $review->id }})"
                    class="w-72 shrink-0 snap-start text-left"
                    aria-label="Revisar {{ $review->title }}"
                >
                    <flux:card size="sm" class="hover:border-primary/50 flex h-full min-h-40 flex-col justify-between transition-colors">
                        <div>
                            <div class="mb-2 flex items-center gap-2">
                                <flux:icon :name="$review->discipline_icon" class="text-primary size-4" />
                                <flux:text size="sm" class="text-on-surface-variant truncate">
                                    {{ $review->discipline_title }}
                                </flux:text>
                            </div>

                            <flux:heading size="sm" class="line-clamp-3">{{ $review->title }}</flux:heading>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
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
            @endforeach
        </div>
    @elseif ($agenda->hasLessonToday)
        <flux:card size="sm" class="flex items-center gap-3">
            <flux:icon.check-circle class="text-primary size-5 shrink-0" />
            <flux:text>Nenhuma revisão pendente para a aula de hoje.</flux:text>
        </flux:card>
    @else
        <flux:card size="sm" class="space-y-3">
            <div class="flex items-center gap-3">
                <flux:icon.calendar-days class="text-on-surface-variant size-5 shrink-0" />
                <flux:text>Hoje não é dia de aula. Próximos encontros:</flux:text>
            </div>

            @forelse ($agenda->upcoming->take(4) as $lesson)
                <div
                    wire:key="upcoming-{{ $loop->index }}"
                    class="border-outline-variant/40 flex flex-wrap items-center justify-between gap-2 border-t pt-3"
                >
                    <flux:text size="sm">
                        <span class="font-medium">{{ $lesson->disciplineTitle }}</span>
                        — {{ $lesson->weekdayLabel }}
                    </flux:text>

                    <flux:badge size="sm" variant="pill" :color="$lesson->dueCount > 0 ? 'amber' : 'zinc'">
                        {{ $lesson->dueLabel() }}
                    </flux:badge>
                </div>
            @empty
                <flux:text size="sm" class="text-on-surface-variant">
                    Defina o dia da aula nas suas disciplinas para o sistema começar a cobrar revisões.
                </flux:text>
            @endforelse
        </flux:card>
    @endif

    <flux:modal
        wire:model="showReviewModal"
        name="revisao-nota"
        class="w-full max-w-[calc(100vw-2rem)] p-0 sm:max-w-2xl"
    >
        @if ($this->noteUnderReview)
            @php($note = $this->noteUnderReview)

            <div class="flex max-h-[85dvh] flex-col" wire:key="review-note-{{ $note->id }}">
                <div class="border-outline-variant/50 shrink-0 border-b px-6 pt-6 pb-4">
                    <flux:heading size="lg" class="pe-10">{{ $note->title }}</flux:heading>

                    <flux:text size="sm" class="text-on-surface-variant mt-1">
                        {{ $note->date() }}
                    </flux:text>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-5">
                    @if ($note->tags)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($note->tags as $tag)
                                <flux:badge size="sm" variant="pill" wire:key="review-tag-{{ $loop->index }}">
                                    {{ $tag }}
                                </flux:badge>
                            @endforeach
                        </div>
                    @endif

                    @if ($note->concepts)
                        <section class="space-y-2">
                            <flux:heading size="sm" class="text-on-surface-variant uppercase">Conceitos</flux:heading>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($note->concepts as $concept)
                                    <flux:badge wire:key="review-concept-{{ $concept->id }}">{{ $concept->term }}</flux:badge>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($quizScore === null)
                        {{-- Fase de resposta: perguntas de múltipla escolha geradas a
                             partir do resumo escrito pelo aluno. Responder é a prova
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

                                        <flux:radio.group wire:model="quizAnswers.{{ $question['id'] }}">
                                            @foreach ($question['options'] as $option)
                                                <flux:radio wire:key="quiz-option-{{ $question['id'] }}-{{ $loop->index }}" value="{{ $option }}" label="{{ $option }}" />
                                            @endforeach
                                        </flux:radio.group>
                                    </div>
                                @endforeach
                            </section>
                        @elseif (! $note->summary)
                            <div class="border-outline-variant/60 flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-10 text-center">
                                <flux:icon.pencil-square class="text-on-surface-variant size-6" />
                                <flux:text>Esta nota ainda não tem resumo escrito, então não há o que cobrar.</flux:text>
                            </div>
                        @else
                            <div class="border-outline-variant/60 flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-10 text-center">
                                <flux:icon.exclamation-triangle class="text-on-surface-variant size-6" />
                                <flux:text>Não foi possível gerar as perguntas desta revisão. Tente novamente mais tarde.</flux:text>
                            </div>
                        @endif
                    @else
                        {{-- Fase de resultado: a correção primeiro, depois o resto da nota. --}}
                        <section class="space-y-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <flux:heading size="sm" class="text-on-surface-variant uppercase">Resultado</flux:heading>
                                <flux:badge size="sm" variant="pill" :color="$lastRecalled ? 'green' : 'amber'">
                                    {{ $quizScore }}%
                                </flux:badge>
                                <flux:text size="sm" class="text-on-surface-variant">
                                    {{ $lastRecalled ? 'Lembrou: a nota sobe um degrau.' : 'A nota volta para o primeiro degrau.' }}
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

                        {{-- Resumo por IA: conferência, depois que o aluno já se
                             comprometeu com uma resposta. Antes disso seria cola. --}}
                        @if ($this->awaitingSummary)
                            <section
                                class="border-surface-variant bg-primary-container/10 space-y-2 rounded-lg border p-4"
                                wire:poll.{{ config('summarizer.poll_interval', '2s') }}="pollCheckSummary"
                            >
                                <div class="flex items-center gap-2">
                                    <flux:icon name="sparkles" class="text-primary size-4 animate-pulse" />
                                    <flux:heading size="xs">Resumo de IA</flux:heading>
                                </div>
                                <div class="bg-surface-variant h-3 w-full animate-pulse rounded"></div>
                                <div class="bg-surface-variant h-3 w-3/4 animate-pulse rounded"></div>
                            </section>
                        @elseif ($note->ai_summary)
                            <section class="border-surface-variant bg-primary-container/10 rounded-lg border p-4" x-data="readAloud(@js($note->ai_summary))">
                                <div class="mb-2 flex items-center gap-2">
                                    <flux:icon name="sparkles" class="text-primary size-4 shrink-0" />
                                    <flux:heading size="xs" class="min-w-0 truncate">Resumo de IA</flux:heading>
                                    <flux:spacer />
                                    <flux:modal.trigger name="confirm-regenerate-summary">
                                        <flux:button size="sm" variant="subtle" class="shrink-0">Gerar novamente</flux:button>
                                    </flux:modal.trigger>
                                </div>

                                <flux:text class="text-sm">{{ $note->ai_summary }}</flux:text>

                                {{-- Locução por IA. Gerada sob demanda porque o free tier do
                                     provedor permite poucas por dia; uma vez pronta, fica
                                     guardada e toca na hora. --}}
                                <div class="mt-3">
                                    @if ($this->summaryAudioUrl)
                                        <div
                                            x-data="aiAudioPlayer"
                                            class="border-outline-variant/40 bg-surface-container flex items-center gap-3 rounded-full border py-2 pr-4 pl-2"
                                        >
                                            <audio
                                                x-ref="audio"
                                                preload="none"
                                                src="{{ $this->summaryAudioUrl }}"
                                                x-on:play="onPlay()"
                                                x-on:pause="onPause()"
                                                x-on:ended="onEnded()"
                                                x-on:timeupdate="current = $event.target.currentTime"
                                                x-on:loadedmetadata="duration = $event.target.duration"
                                                class="hidden"
                                            ></audio>

                                            <button
                                                type="button"
                                                x-on:click="toggle()"
                                                class="bg-primary text-on-primary hover:bg-primary-container flex size-9 shrink-0 items-center justify-center rounded-full transition-colors"
                                                x-bind:aria-label="playing ? 'Pausar locução' : 'Ouvir locução'"
                                            >
                                                <flux:icon name="play" variant="micro" x-show="!playing" />
                                                <flux:icon name="pause" variant="micro" x-show="playing" x-cloak />
                                            </button>

                                            <div
                                                x-on:click="seek($event)"
                                                class="flex h-9 min-w-0 flex-1 cursor-pointer items-center gap-[2px]"
                                                role="slider"
                                                aria-label="Posição da locução"
                                                x-bind:aria-valuenow="Math.round(progress)"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                            >
                                                <template x-for="(height, i) in bars" :key="i">
                                                    <span
                                                        class="flex-1 rounded-full transition-[height,background-color] duration-75"
                                                        x-bind:class="(i / bars.length) * 100 <= progress ? 'bg-primary' : 'bg-outline-variant'"
                                                        x-bind:style="`height: ${playing ? height : 12}%`"
                                                    ></span>
                                                </template>
                                            </div>

                                            <span
                                                class="text-on-surface-variant shrink-0 font-mono text-xs tabular-nums"
                                                x-text="format(duration - current)"
                                            >0:00</span>
                                        </div>
                                    @elseif ($this->awaitingAudio)
                                        <div
                                            class="text-on-surface-variant flex items-center gap-2 text-xs"
                                            wire:poll.{{ config('tts.poll_interval') }}="pollCheckAudio"
                                        >
                                            <flux:icon name="loading" variant="micro" />
                                            Gerando a locução. Leva cerca de um minuto.
                                        </div>
                                    @else
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="musical-note"
                                            wire:click="generateSummaryAudio"
                                            wire:loading.attr="disabled"
                                        >
                                            Ouvir com voz de IA
                                        </flux:button>
                                    @endif

                                    {{-- Leitura pela voz do navegador: instantânea e sem cota,
                                         serve de alternativa à locução de IA. --}}
                                    <div class="mt-2 flex items-center gap-1">
                                        <flux:text size="xs" class="text-on-surface-variant mr-1">Voz do navegador</flux:text>
                                        <flux:button size="xs" variant="ghost" icon="speaker-wave" aria-label="Ler em português" x-on:click="read('pt-BR')">🇧🇷</flux:button>
                                        <flux:button size="xs" variant="ghost" icon="speaker-wave" aria-label="Read in English" x-on:click="read('en-US')">🇺🇸</flux:button>
                                    </div>
                                </div>
                            </section>
                        @else
                            <div>
                                <flux:button
                                    size="sm"
                                    variant="subtle"
                                    icon="sparkles"
                                    wire:click="generateSummary"
                                >
                                    Gerar resumo com IA
                                </flux:button>
                            </div>
                        @endif

                        @if ($note->concepts)
                            <section class="space-y-3">
                                <flux:heading size="sm" class="text-on-surface-variant uppercase">Definições</flux:heading>

                                @foreach ($note->concepts as $concept)
                                    <div wire:key="review-definition-{{ $concept->id }}">
                                        <flux:text class="font-medium">{{ $concept->term }}</flux:text>
                                        <flux:text class="whitespace-pre-line">{{ $concept->definition }}</flux:text>
                                    </div>
                                @endforeach
                            </section>
                        @endif

                        @if ($note->impressions)
                            <section class="space-y-2">
                                <flux:heading size="sm" class="text-on-surface-variant uppercase">Impressões</flux:heading>
                                <flux:text class="whitespace-pre-line">{{ $note->impressions }}</flux:text>
                            </section>
                        @endif

                        @if ($note->life_experiences)
                            <section class="space-y-2">
                                <flux:heading size="sm" class="text-on-surface-variant uppercase">Experiências</flux:heading>
                                <flux:text class="whitespace-pre-line">{{ $note->life_experiences }}</flux:text>
                            </section>
                        @endif

                        @if ($note->pastoral_advice)
                            <section class="space-y-3">
                                <flux:heading size="sm" class="text-on-surface-variant uppercase">Conselhos pastorais</flux:heading>

                                @foreach ($note->pastoral_advice as $advice)
                                    <div wire:key="review-advice-{{ $advice->id }}">
                                        @if ($advice->category)
                                            <flux:badge size="sm" variant="pill">{{ $advice->category }}</flux:badge>
                                        @endif
                                        <flux:text class="mt-1 whitespace-pre-line">{{ $advice->advice }}</flux:text>
                                    </div>
                                @endforeach
                            </section>
                        @endif

                        @if ($note->reference_materials)
                            <section class="space-y-2">
                                <flux:heading size="sm" class="text-on-surface-variant uppercase">Referências</flux:heading>

                                @foreach ($note->reference_materials as $material)
                                    <flux:text size="sm" wire:key="review-material-{{ $material['id'] }}">
                                        {{ $material['title'] }}@if ($material['author']) — {{ $material['author'] }}@endif
                                    </flux:text>
                                @endforeach
                            </section>
                        @endif
                    @endif
                </div>

                <div class="border-outline-variant/50 bg-surface-container-low shrink-0 border-t px-6 py-4">
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
                                Próxima nota
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="confirm-regenerate-summary" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Regenerar resumo?</flux:heading>
                <flux:text class="mt-2">O resumo de IA atual será substituído por um novo.</flux:text>
            </div>
            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="generateSummary">Regenerar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
