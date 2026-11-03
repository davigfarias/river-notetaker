<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="lg" level="2">Mapa mental</flux:heading>

        @include('partials.concept-map-scratchpad', ['note' => 'Só em memória: some ao trocar de aba ou sair da página.'])
    </div>

    @include('partials.concept-map', [
        'concepts' => $this->concepts,
        'nodes' => $this->nodes,
        'edges' => $this->edges,
        'emptyHint' => 'Nenhum conceito marcado nas anotações desta obra ainda. Na aba Anotações, selecione uma palavra no editor e clique no ícone de alvo (ou aperte Ctrl/Cmd+Alt+N).',
    ])
</div>
