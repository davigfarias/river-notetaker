@placeholder
    <div class="mx-auto w-full max-w-7xl py-8">
        <div class="mb-8">
            <flux:heading size="xl" level="1">Princípios</flux:heading>
            <flux:text class="mt-2">Temas e os princípios reunidos em cada um.</flux:text>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach (range(1, 3) as $i)
                <div class="border-surface-variant bg-surface-container-lowest relative flex min-h-40 flex-col justify-between overflow-hidden rounded-xl border p-6 shadow-sm">
                    <flux:skeleton class="h-5 w-3/4" />
                    <flux:skeleton class="h-4 w-1/3" />
                </div>
            @endforeach
        </div>
    </div>
@endplaceholder

<div>
    <div class="mx-auto w-full max-w-7xl py-8">

        <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">Princípios</flux:heading>
                <flux:text class="mt-2">Temas e os princípios reunidos em cada um.</flux:text>
            </div>

            <div>
                <flux:modal.trigger name="add-topic">
                    <flux:button icon="plus">Novo tema</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        @if ($this->topics->isEmpty())
            <div class="flex flex-col items-center justify-center py-24 px-6 text-center rounded-xl border border-surface-variant bg-surface-container-low border-dashed">
                <flux:icon name="scale" class="size-10 text-surface-variant-content/50 mb-3" />
                <flux:heading size="md">Nenhum tema cadastrado</flux:heading>
                <flux:text class="mt-2 text-surface-variant-content">Cadastre um tema para começar a reunir princípios.</flux:text>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->topics as $topic)
                    <x-card-tile
                        wire:key="topic-{{ $topic->id }}"
                        :href="route('principios.show', $topic->slug)"
                        :label="$topic->title"
                    >
                        <flux:badge size="sm" class="self-start">{{ $topic->principles_count }} {{ \Illuminate\Support\Str::plural('princípio', $topic->principles_count) }}</flux:badge>
                    </x-card-tile>
                @endforeach
            </div>
        @endif

        <flux:modal name="add-topic" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
            <form wire:submit="createTopic" class="space-y-5">
                <flux:heading size="lg">Novo tema</flux:heading>
                <flux:input label="Título" wire:model="form.title" placeholder="Ex: Soteriologia" />
                <flux:error name="form.title" />
                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Criar</flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
</div>
