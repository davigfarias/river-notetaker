@php($withAll = $includeAll ?? false)
@php($isLive = $live ?? false)

@if ($isLive)
    <flux:radio.group wire:model.live="{{ $model }}" variant="pills" :label="$label ?? null" class="flex flex-wrap gap-2">
        @if ($withAll)
            <flux:radio value="">Todos</flux:radio>
        @endif
        @foreach (\App\Enums\ReferencesIcon::cases() as $case)
            <flux:radio value="{{ $case->value }}">
                <span class="flex items-center gap-1.5">
                    <flux:icon :name="$case->icon()" class="size-4" />{{ $case->label() }}
                </span>
            </flux:radio>
        @endforeach
    </flux:radio.group>
@else
    <flux:radio.group wire:model="{{ $model }}" variant="pills" :label="$label ?? null" class="flex flex-wrap gap-2">
        @foreach (\App\Enums\ReferencesIcon::cases() as $case)
            <flux:radio value="{{ $case->value }}">
                <span class="flex items-center gap-1.5">
                    <flux:icon :name="$case->icon()" class="size-4" />{{ $case->label() }}
                </span>
            </flux:radio>
        @endforeach
    </flux:radio.group>
@endif
