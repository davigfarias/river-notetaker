@php
    $dias = fn (int $d): string => $d === 1 ? 'volta em 1 dia' : "volta em {$d} dias";
@endphp

<x-slot:headerActions>
    <flux:button variant="ghost" icon="arrow-left" href="{{ route('principios') }}" wire:navigate>Princípios</flux:button>
</x-slot:headerActions>

<main class="mx-auto flex w-full max-w-[720px] flex-col gap-6 py-8 pb-32">

    @if ($step === \App\Enums\StudyStep::Setup)
        <div>
            <flux:heading size="xl" level="1">Estudar princípios</flux:heading>
            <flux:text class="mt-2">Uma sessão curta que mistura lembrar, explicar, aplicar e criar. Você tenta responder antes de ver o gabarito.</flux:text>
        </div>

        @if ($this->studyCount === 0 && ! $includeNotDue)
            <x-empty-state icon="check-badge" heading="Nada para revisar hoje" description="Todos os princípios estão em dia, ou ainda não há princípios cadastrados. Você pode estudar mesmo assim marcando a opção abaixo.">
            </x-empty-state>
        @else
            <flux:badge color="blue" class="self-start">{{ $this->studyCount }} {{ $this->studyCount === 1 ? 'princípio' : 'princípios' }} para hoje</flux:badge>
        @endif

        <form wire:submit="start" class="space-y-5">
            <flux:select wire:model="disciplineId" label="Disciplina (opcional)">
                <flux:select.option value="">Todas as disciplinas</flux:select.option>
                @foreach ($this->disciplines as $discipline)
                    <flux:select.option value="{{ $discipline->id }}" wire:key="disc-{{ $discipline->id }}">{{ $discipline->title }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="disciplineId" />

            <flux:select wire:model="categoryId" label="Categoria (opcional)">
                <flux:select.option value="">Todas as categorias</flux:select.option>
                @foreach ($this->categories as $category)
                    <flux:select.option value="{{ $category->id }}" wire:key="cat-{{ $category->id }}">{{ $category->principleTopic?->title }} › {{ $category->title }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="categoryId" />

            <flux:checkbox wire:model="includeNotDue" label="Estudar mesmo os que ainda não venceram" description="Completa a sessão com os princípios que vencem logo." />

            <flux:button type="submit" variant="primary" icon="play" wire:loading.attr="disabled" wire:target="start">Começar sessão</flux:button>
        </form>

    @elseif ($step === \App\Enums\StudyStep::Summary)
        @php $summary = $this->summary; @endphp
        <div>
            <flux:heading size="xl" level="1">Sessão concluída</flux:heading>
            <flux:text class="mt-2">Veja como foi e quando cada princípio volta.</flux:text>
        </div>

        @if ($summary === null || $summary->answered === 0)
            <x-empty-state icon="inbox" heading="Nenhum item foi respondido" description="Você encerrou a sessão antes de avaliar algum princípio, então nada mudou na sua agenda." />
        @else
            <dl class="grid grid-cols-2 gap-4">
                <div class="rounded-xl border border-outline-variant bg-surface-container-low p-4">
                    <dt class="text-sm text-on-surface-variant">Itens feitos</dt>
                    <dd class="text-2xl font-semibold">{{ $summary->answered }}</dd>
                </div>
                <div class="rounded-xl border border-outline-variant bg-surface-container-low p-4">
                    <dt class="text-sm text-on-surface-variant">Tempo total</dt>
                    <dd class="text-2xl font-semibold">{{ intdiv($summary->totalSeconds, 60) }} min {{ $summary->totalSeconds % 60 }} s</dd>
                </div>
            </dl>

            <div class="flex flex-wrap gap-2">
                @foreach ($summary->byLevel as $level => $count)
                    <flux:badge wire:key="lvl-{{ $level }}">{{ $level }}: {{ $count }}</flux:badge>
                @endforeach
            </div>

            <flux:text>
                @if ($summary->nextDueAt)
                    Próxima revisão: {{ $summary->nextDueAt->translatedFormat('d \d\e F') }}.
                @endif
                @if ($summary->missedPrincipleIds !== [])
                    Você marcou "Errei" em {{ count($summary->missedPrincipleIds) }} {{ count($summary->missedPrincipleIds) === 1 ? 'princípio' : 'princípios' }}.
                @else
                    Você não marcou nenhum "Errei".
                @endif
            </flux:text>
        @endif

        <div class="flex flex-wrap gap-3">
            @if ($summary !== null && $summary->missedPrincipleIds !== [])
                <flux:button wire:click="reviewMissed" variant="primary" icon="arrow-path">Revisar apenas os que errei</flux:button>
            @endif
            <flux:button wire:click="newSession" icon="plus">Nova sessão</flux:button>
            <flux:button :href="route('principios')" wire:navigate variant="ghost">Voltar aos princípios</flux:button>
        </div>

    @else
        @php $exercise = $this->exercise; @endphp

        @if ($exercise === null)
            <x-empty-state icon="exclamation-triangle" heading="Este item da sessão não existe mais" description="O princípio pode ter sido apagado. Encerre esta sessão e comece outra.">
                <flux:button wire:click="finishNow" variant="primary">Encerrar sessão</flux:button>
            </x-empty-state>
        @else
            <div class="flex flex-wrap items-center gap-3">
                <flux:badge>{{ $exercise->type->bloomLevel()->label() }}</flux:badge>
                @if ($this->item['retry'] ?? false)
                    <flux:badge color="amber">Segunda chance</flux:badge>
                @endif
                <span class="text-sm text-on-surface-variant">{{ $index + 1 }} de {{ $this->plannedItems }}</span>
                <flux:spacer />
                <flux:modal.trigger name="leave-study">
                    <flux:button size="sm" variant="ghost" icon="arrow-right-start-on-rectangle">Sair da sessão</flux:button>
                </flux:modal.trigger>
            </div>

            <flux:progress :value="$this->plannedItems === 0 ? 0 : (int) round(($index / $this->plannedItems) * 100)" />

            <div wire:key="exercise-{{ $index }}" class="space-y-4">
                <div class="rounded-xl border border-t-4 border-outline-variant border-t-primary bg-surface-container-low p-6 shadow-sm">
                    <p class="whitespace-pre-line text-lg font-medium text-on-surface">{{ $exercise->question }}</p>
                </div>

                <flux:text>{{ $exercise->instruction }}</flux:text>

                @if ($step === \App\Enums\StudyStep::Question)
                    <flux:textarea wire:model="answer" rows="5" maxlength="5000" autofocus
                        label="Sua resposta{{ $exercise->requiresAnswer ? '' : ' (opcional)' }}"
                        placeholder="Escreva aqui antes de ver o gabarito." />

                    <flux:button wire:click="reveal" variant="primary" icon="eye" wire:loading.attr="disabled" wire:target="reveal">
                        {{ $exercise->requiresAnswer ? 'Verificar' : 'Mostrar resposta' }}
                    </flux:button>
                @else
                    <div aria-live="polite" class="space-y-4">
                        @if ($answer !== '')
                            <div class="rounded-xl border border-outline-variant p-4">
                                <p class="mb-1 text-sm font-semibold text-on-surface-variant">Sua resposta</p>
                                <p class="whitespace-pre-line">{{ $answer }}</p>
                            </div>
                        @endif

                        <div class="rounded-xl border border-t-4 border-outline-variant border-t-secondary bg-surface-container-low p-6">
                            <p class="mb-1 text-sm font-semibold text-on-surface-variant">{{ $exercise->referenceLabel }}</p>
                            <p class="whitespace-pre-line text-on-surface">{{ $exercise->reference }}</p>
                        </div>

                        @if ($exercise->type === \App\Enums\ExerciseType::CreateExample)
                            <flux:text size="sm">Sem IA por enquanto: compare seu exemplo com o princípio e escolha a nota que for honesta.</flux:text>
                        @endif
                    </div>

                    <flux:heading size="md" level="2">Como foi?</flux:heading>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach (\App\Enums\SelfRating::cases() as $rating)
                            <div class="flex flex-col items-center gap-1" wire:key="rate-{{ $index }}-{{ $rating->value }}">
                                <flux:button wire:click="rate('{{ $rating->value }}')" wire:loading.attr="disabled" wire:target="rate" class="w-full justify-center" :variant="$rating === \App\Enums\SelfRating::Good ? 'primary' : 'outline'">{{ $rating->label() }}</flux:button>
                                <span class="text-xs text-on-surface-variant">{{ $dias($this->intervals[$rating->value] ?? 1) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <flux:modal name="leave-study" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <div class="space-y-4">
                <flux:heading size="lg">Sair da sessão?</flux:heading>
                <flux:text>O que você já avaliou fica salvo. Você pode continuar depois de onde parou, ou encerrar a sessão agora.</flux:text>
                <div class="flex flex-col gap-2">
                    <flux:button wire:click="leave" variant="primary">Continuar depois</flux:button>
                    <flux:button wire:click="finishNow">Encerrar agora</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</main>
