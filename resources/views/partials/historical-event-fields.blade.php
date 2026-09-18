{{-- Campos do formulário de evento histórico. Espera $model = nome do form no componente (eventForm / editEventForm). --}}
<flux:field>
    <flux:label>Título</flux:label>
    <flux:input wire:model="{{ $model }}.title" placeholder="Ex.: Publicação das 95 Teses" />
    <flux:error name="{{ $model }}.title" />
</flux:field>

<flux:field>
    <flux:label>Natureza</flux:label>
    <flux:select wire:model="{{ $model }}.nature">
        @foreach (\App\Enums\HistoricalEventNature::cases() as $nature)
            <flux:select.option value="{{ $nature->value }}" wire:key="{{ $model }}-nature-{{ $nature->value }}">
                {{ $nature->label() }}
            </flux:select.option>
        @endforeach
    </flux:select>
    <flux:error name="{{ $model }}.nature" />
</flux:field>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <flux:field>
        <flux:label>Ano</flux:label>
        <flux:input type="number" min="1" max="9999" wire:model="{{ $model }}.start_year" placeholder="1517" />
        <flux:error name="{{ $model }}.start_year" />
    </flux:field>

    <flux:radio.group wire:model="{{ $model }}.start_era" variant="segmented" label="Era">
        @foreach (\App\Enums\Era::cases() as $era)
            <flux:radio value="{{ $era->value }}">{{ $era->label() }}</flux:radio>
        @endforeach
    </flux:radio.group>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <flux:field>
        <flux:label>Ano de fim (opcional)</flux:label>
        <flux:input type="number" min="1" max="9999" wire:model="{{ $model }}.end_year" placeholder="Para intervalos" />
        <flux:error name="{{ $model }}.end_year" />
    </flux:field>

    <flux:radio.group wire:model="{{ $model }}.end_era" variant="segmented" label="Era do fim">
        @foreach (\App\Enums\Era::cases() as $era)
            <flux:radio value="{{ $era->value }}">{{ $era->label() }}</flux:radio>
        @endforeach
    </flux:radio.group>
</div>
<flux:error name="{{ $model }}.end_era" />

<flux:field>
    <flux:label>Descrição (opcional)</flux:label>
    <flux:textarea wire:model="{{ $model }}.description" rows="4" placeholder="O que aconteceu e por que importa." />
    <flux:error name="{{ $model }}.description" />
</flux:field>
