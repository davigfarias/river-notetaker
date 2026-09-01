<x-slot:headerActions>
    <flux:button variant="ghost" icon="arrow-left" href="{{ route('referencias.show', $referenceMaterialId) }}" wire:navigate>
        {{ $this->chapter->referenceMaterial->title }}
    </flux:button>
</x-slot:headerActions>

<main class="max-w-[1280px] mx-auto px-6 py-8">
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Resultados da sessão</flux:heading>
            <flux:text class="mt-1">{{ $this->chapter->referenceMaterial->title }} — {{ $this->results->chapterTitle }}</flux:text>
        </div>

        <div class="flex gap-4 bg-surface-container-low p-4 rounded-xl border border-outline-variant/50">
            <div class="flex flex-col">
                <span class="font-mono text-sm text-on-surface-variant uppercase tracking-wider">Proximidade média</span>
                <span class="font-heading text-3xl text-tertiary-fixed">{{ $this->results->averageScore }}%</span>
            </div>
            <div class="w-px bg-surface-variant"></div>
            <div class="flex flex-col">
                <span class="font-mono text-sm text-on-surface-variant uppercase tracking-wider">Perguntas</span>
                <span class="font-heading text-3xl text-on-surface">{{ $this->results->questionCount }}</span>
            </div>
        </div>
    </div>

    @if ($this->results->rows === [])
        <flux:callout icon="inbox">
            <flux:callout.heading>Nenhuma pergunta respondida ainda</flux:callout.heading>
            <flux:callout.text>Estude este capítulo para ver seus resultados aqui.</flux:callout.text>
        </flux:callout>
    @else
        <div class="flex flex-col gap-6 pb-12">
            @foreach ($this->results->rows as $row)
                @php
                    $tier = match (true) {
                        $row->skipped => 'skipped',
                        $row->score >= 70 => 'high',
                        $row->score >= 40 => 'medium',
                        default => 'low',
                    };

                    $badgeClasses = match ($tier) {
                        'high' => 'bg-tertiary-fixed/15 border-tertiary-fixed/30 text-tertiary-fixed',
                        'medium' => 'bg-secondary/15 border-secondary/30 text-secondary',
                        'low' => 'bg-error/15 border-error/30 text-error',
                        default => 'bg-surface-variant border-outline-variant text-on-surface-variant',
                    };
                @endphp

                <div class="bg-surface rounded-xl p-6 border border-outline-variant flex flex-col gap-6" wire:key="result-{{ $row->position }}">
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-surface-variant flex items-center justify-center shrink-0 mt-1">
                            <span class="font-mono text-sm text-on-surface">Q{{ $row->position }}</span>
                        </div>
                        <div class="flex-1">
                            <flux:heading size="lg" class="mb-2">{{ $row->prompt }}</flux:heading>

                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border {{ $badgeClasses }} mb-4">
                                @if ($tier === 'skipped')
                                    <flux:icon.forward class="size-4" />
                                    <span class="text-sm">Pulada</span>
                                @else
                                    <flux:icon.check-circle class="size-4" />
                                    <span class="text-sm">{{ $row->score }}% {{ $row->clozeSegments !== null ? 'de acertos' : 'de proximidade' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @unless ($row->skipped)
                        @if ($row->clozeSegments !== null)
                        <div class="ml-0 md:ml-12">
                            <div class="relative rounded-lg bg-surface-container-low p-4 pl-5 overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-secondary-container"></div>
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:icon.pencil-square class="size-4 text-secondary-fixed" />
                                    <span class="font-mono text-sm text-secondary-fixed">Frase</span>
                                </div>
                                <p class="font-sans text-sm leading-loose">@foreach ($row->clozeSegments as $seg)@if ($seg->blank)@if ($seg->correct)<span class="text-tertiary-fixed font-medium">{{ $seg->given }}</span>@else<span class="text-error/80 line-through decoration-1">{{ $seg->given ?: '—' }}</span> <span class="text-secondary">{{ $seg->expected }}</span>@endif@else{{ $seg->text }}@endif@endforeach</p>
                            </div>
                        </div>
                        @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 ml-0 md:ml-12">
                            <div class="relative rounded-lg bg-surface-container-low p-4 pl-5 overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-secondary-container"></div>
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:icon.shield-check class="size-4 text-secondary-fixed" />
                                    <span class="font-mono text-sm text-secondary-fixed">Resposta padrão</span>
                                </div>
                                <p class="text-sm text-on-surface-variant">{{ $row->referenceAnswer }}</p>
                            </div>

                            <div class="relative rounded-lg bg-surface-container-low p-4 pl-5 overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full {{ match ($tier) { 'high' => 'bg-tertiary-fixed', 'medium' => 'bg-secondary', default => 'bg-error' } }}"></div>
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:icon.user class="size-4 {{ match ($tier) { 'high' => 'text-tertiary-fixed', 'medium' => 'text-secondary', default => 'text-error' } }}" />
                                    <span class="font-mono text-sm {{ match ($tier) { 'high' => 'text-tertiary-fixed', 'medium' => 'text-secondary', default => 'text-error' } }}">Sua resposta</span>
                                </div>
                                <p class="font-sans text-sm">
                                    @foreach ($row->answerSegments as $segment)
                                        <span class="{{ $segment->matched ? 'text-on-surface' : 'text-error/80 line-through decoration-1' }}">{{ $segment->text }}</span>{{ $loop->last ? '' : ' ' }}
                                    @endforeach
                                </p>
                            </div>
                        </div>
                        @endif
                    @endunless
                </div>
            @endforeach
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        <flux:button :href="route('referencias.study', ['id' => $referenceMaterialId, 'chapterId' => $chapterId])" wire:navigate variant="primary" icon="arrow-path">
            Estudar novamente
        </flux:button>
        <flux:button :href="route('referencias.show', $referenceMaterialId)" wire:navigate variant="ghost">
            Voltar à obra
        </flux:button>
    </div>
</main>
