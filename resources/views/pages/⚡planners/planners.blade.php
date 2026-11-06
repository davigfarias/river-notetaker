<div class="w-full py-8">
    @include('partials.referencias-nav', ['active' => 'planners'])

    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl" level="1">Planners de leitura</flux:heading>
            <flux:text class="mt-2">Junte capítulos e obras em torno de um objetivo e marque o que você já leu.</flux:text>
        </div>

        <flux:button icon="plus" wire:click="openCreate">Novo planner</flux:button>
    </div>

    @forelse ($this->planners as $planner)
        @php($progress = $planner->progress())
        <a
            wire:key="planner-{{ $planner->id }}"
            href="{{ route('referencias.planners.show', $planner->id) }}"
            wire:navigate
            class="border-surface-variant bg-surface-container-lowest hover:border-primary/50 mb-3 block rounded-xl border p-5 transition-colors"
        >
            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <flux:heading size="lg">{{ $planner->name }}</flux:heading>
                <flux:text size="sm" class="tabular-nums">
                    @if ($progress['total'] === 0)
                        Sem itens ainda
                    @elseif ($progress['read'] === $progress['total'])
                        Concluído · {{ $progress['total'] }} {{ $progress['total'] === 1 ? 'item lido' : 'itens lidos' }}
                    @else
                        {{ $progress['read'] }} de {{ $progress['total'] }} lidos · {{ $progress['percent'] }}%
                    @endif
                </flux:text>
            </div>

            @if ($planner->objective)
                <flux:text class="mt-1 line-clamp-2">{{ $planner->objective }}</flux:text>
            @endif

            <x-planner-progress :read="$progress['read']" :total="$progress['total']" class="mt-4" />

            @if ($planner->target_date)
                <flux:text size="sm" class="mt-3">Meta: {{ $planner->target_date->format('d/m/Y') }}</flux:text>
            @endif
        </a>
    @empty
        <x-empty-state
            icon="map"
            heading="Nenhum planner ainda."
            description="Um planner junta capítulos e obras em torno de um objetivo de leitura. Crie o primeiro e escolha o que entra nele."
        />
    @endforelse

    <flux:modal name="create-planner" wire:model.self="creating" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-md">
        <form wire:submit="create" class="space-y-5">
            <flux:heading size="lg">Novo planner</flux:heading>

            <flux:input label="Nome" wire:model="form.name" placeholder="Ex.: Doutrina da graça" x-init="$el.focus()" />
            <flux:error name="form.name" />

            <flux:textarea label="Objetivo (opcional)" wire:model="form.objective" rows="3" placeholder="Para que você quer ler isto?" />
            <flux:error name="form.objective" />

            <flux:input type="date" label="Data-alvo (opcional)" wire:model="form.targetDate" />
            <flux:error name="form.targetDate" />

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Criar planner</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
