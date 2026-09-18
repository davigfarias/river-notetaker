<div class="mb-10">
    @php($agenda = $this->agenda)

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:heading size="lg">Revisões de hoje</flux:heading>
        <flux:badge size="sm" variant="pill" :color="$agenda->isEmpty() ? 'zinc' : 'amber'">
            {{ $agenda->totalDue }}
        </flux:badge>
        <flux:text size="sm" class="text-on-surface-variant">{{ $agenda->today->label() }}</flux:text>
    </div>

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

                    @unless ($revealed)
                        <div class="border-outline-variant/60 flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-10 text-center">
                            <flux:icon.eye-slash class="text-on-surface-variant size-6" />
                            <flux:text>Tente lembrar o conteúdo desta nota antes de revelar.</flux:text>
                            <flux:button type="button" variant="primary" icon="eye" wire:click="reveal">
                                Revelar a nota
                            </flux:button>
                        </div>
                    @else
                        @if ($note->ai_summary)
                            <section class="space-y-2">
                                <flux:heading size="sm" class="text-on-surface-variant uppercase">Resumo</flux:heading>
                                <flux:text class="whitespace-pre-line">{{ $note->ai_summary }}</flux:text>
                            </section>
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
                    @endunless
                </div>

                <div class="border-outline-variant/50 bg-surface-container-low shrink-0 border-t px-6 py-4">
                    @if ($revealed)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button type="button" variant="ghost" wire:click="closeReview">
                                Parar por aqui
                            </flux:button>

                            <flux:spacer />

                            <flux:button type="button" variant="danger" icon="arrow-path" wire:click="judge(false)">
                                Travei
                            </flux:button>

                            <flux:button type="button" variant="primary" icon="check" wire:click="judge(true)">
                                Lembrei
                            </flux:button>
                        </div>
                    @else
                        <flux:text size="sm" class="text-on-surface-variant">
                            Os botões aparecem depois de revelar a nota.
                        </flux:text>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>
</div>
