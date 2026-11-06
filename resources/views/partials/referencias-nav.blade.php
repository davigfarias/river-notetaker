{{-- Alterna entre a biblioteca de obras e os planners de leitura. --}}
<nav aria-label="Seções de Referências" class="mb-6 flex gap-2">
    <flux:button size="sm" :variant="$active === 'obras' ? 'filled' : 'ghost'" icon="book-open" href="{{ route('referencias') }}" wire:navigate :aria-current="$active === 'obras' ? 'page' : null">Obras</flux:button>
    <flux:button size="sm" :variant="$active === 'planners' ? 'filled' : 'ghost'" icon="map" href="{{ route('referencias.planners') }}" wire:navigate :aria-current="$active === 'planners' ? 'page' : null">Planners</flux:button>
</nav>
