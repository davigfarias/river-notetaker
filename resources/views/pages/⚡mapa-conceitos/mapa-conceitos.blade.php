<div class="w-full py-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ $discipline->title }}</flux:heading>
            <flux:text class="mt-1">Mapa de conceitos</flux:text>
        </div>

        @include('partials.concept-map-scratchpad', ['note' => 'Só em memória: some ao sair da página.'])

        <flux:button variant="ghost" icon="document-text" href="{{ route('disciplinas.show', $discipline->slug) }}" wire:navigate>Notas</flux:button>
    </div>

    @include('partials.concept-map', [
        'concepts' => $this->concepts,
        'nodes' => $this->nodes,
        'edges' => $this->edges,
        'emptyHint' => new \Illuminate\Support\HtmlString('Marque conceitos nas notas com <code>&#123;&#123;no: Conceito&#125;&#125;</code>.'),
    ])
</div>
