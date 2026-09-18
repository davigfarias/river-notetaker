<flux:field>
    <flux:label>Nome da disciplina</flux:label>
    <flux:input wire:model="dto.title" placeholder="Ex: Homilética" autofocus />
    <flux:error name="dto.title" />
</flux:field>

<div class="grid grid-cols-2 gap-4">
    <flux:field>
        <flux:label>Período</flux:label>
        <flux:select wire:model="dto.period" placeholder="Sem período">
            <flux:select.option value="">Sem período</flux:select.option>
            @foreach (range(1, 12) as $period)
                <flux:select.option value="{{ $period }}">{{ $period }}º período</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="dto.period" />
    </flux:field>

    <flux:field>
        <flux:label>Código</flux:label>
        <flux:input wire:model="dto.code" placeholder="Ex: TEO-101" />
        <flux:error name="dto.code" />
    </flux:field>
</div>

<flux:field>
    <flux:label>Dia da aula</flux:label>
    <flux:description>Define quando as revisões desta disciplina são cobradas no painel.</flux:description>
    <flux:select wire:model="dto.class_weekday" placeholder="Sem dia definido">
        <flux:select.option value="">Sem dia definido</flux:select.option>
        @foreach (App\Enums\Weekday::options() as $weekday)
            <flux:select.option value="{{ $weekday->value }}" wire:key="class-weekday-{{ $weekday->value }}">
                {{ $weekday->label() }}
            </flux:select.option>
        @endforeach
    </flux:select>
    <flux:error name="dto.class_weekday" />
</flux:field>

<flux:field variant="inline">
    <flux:switch wire:model="dto.is_completed" />
    <flux:label>Disciplina encerrada</flux:label>
    <flux:description>Uma disciplina encerrada para de cobrar revisões.</flux:description>
    <flux:error name="dto.is_completed" />
</flux:field>

<flux:field>
    <flux:label>Professor responsável</flux:label>
    <flux:input wire:model="dto.professor" placeholder="Ex: Rev. João da Silva" />
    <flux:error name="dto.professor" />
</flux:field>

<flux:field>
    <flux:label>Ícone</flux:label>
    <div class="overflow-x-auto pb-1">
        <flux:radio.group wire:model="dto.icon" variant="buttons" class="flex-nowrap">
            @foreach (App\Enums\DisciplineIcon::options() as $icon)
                <flux:radio value="{{ $icon }}" icon="{{ $icon }}" wire:key="discipline-icon-{{ $icon }}" />
            @endforeach
        </flux:radio.group>
    </div>
    <flux:error name="dto.icon" />
</flux:field>
