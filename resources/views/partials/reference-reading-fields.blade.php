{{-- Book tracker fields for a ReferenceMaterialForm ($model = 'form' | 'editForm'). --}}
<div
    x-data="{
        get trackable() {
            return ['book-open', 'newspaper'].includes($wire.$get('{{ $model }}.type'));
        },
        get digital() {
            return ['kindle', 'apple_books'].includes($wire.$get('{{ $model }}.book_format'));
        },
    }"
    x-show="trackable"
    x-cloak
    class="space-y-4 rounded-lg border border-surface-variant bg-surface-container-low/50 p-3"
>
    <flux:radio.group
        wire:model.live="{{ $model }}.book_format"
        variant="segmented"
        label="Formato"
    >
        @foreach (\App\Enums\BookFormat::cases() as $case)
            <flux:radio value="{{ $case->value }}">{{ $case->label() }}</flux:radio>
        @endforeach
    </flux:radio.group>

    <div x-show="digital" class="flex gap-3">
        <flux:input
            label="Página inicial"
            type="number"
            wire:model="{{ $model }}.reader_start_page"
            placeholder="Ex: 12"
            class="flex-1"
        />
        <flux:input
            label="Página final"
            type="number"
            wire:model="{{ $model }}.reader_end_page"
            placeholder="Ex: 340"
            class="flex-1"
        />
    </div>
    <flux:error name="{{ $model }}.reader_start_page" />
    <flux:error name="{{ $model }}.reader_end_page" />

    <div x-show="! digital">
        <flux:input
            label="Total de páginas"
            type="number"
            wire:model="{{ $model }}.page_count"
            placeholder="Ex: 240"
        />
        <flux:error name="{{ $model }}.page_count" />
    </div>

    <flux:radio.group
        wire:model="{{ $model }}.reading_status"
        variant="segmented"
        label="Status de leitura"
    >
        @foreach (\App\Enums\ReadingStatus::cases() as $case)
            <flux:radio value="{{ $case->value }}">{{ $case->label() }}</flux:radio>
        @endforeach
    </flux:radio.group>

    <div class="flex gap-3">
        <flux:input label="Início da leitura" type="date" wire:model="{{ $model }}.reading_started_at" class="flex-1" />
        <flux:input label="Fim da leitura" type="date" wire:model="{{ $model }}.reading_finished_at" class="flex-1" />
    </div>
    <flux:error name="{{ $model }}.reading_finished_at" />
</div>
