<div class="w-full py-8">
    <flux:tab.group>
        <flux:tabs wire:model.live="aba" aria-label="Conceitos">
            <flux:tab name="decks" icon="rectangle-stack">Decks</flux:tab>
            <flux:tab name="dicionario" icon="book-open">Dicionário</flux:tab>
        </flux:tabs>

        {{-- Só a aba aberta é montada: o Dicionário carrega sob demanda e os decks sempre mostram números frescos. --}}
        <flux:tab.panel name="decks" :selected="$aba === 'decks'">
            @if ($aba === 'decks')
                <livewire:decks-de-conceitos wire:key="aba-decks" />
            @endif
        </flux:tab.panel>

        <flux:tab.panel name="dicionario" :selected="$aba === 'dicionario'">
            @if ($aba === 'dicionario')
                <livewire:pages::concepts wire:key="aba-dicionario" />
            @endif
        </flux:tab.panel>
    </flux:tab.group>
</div>
