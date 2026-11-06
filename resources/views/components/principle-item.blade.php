@props(['principle', 'editing' => false, 'mastery' => null])

<x-timeline.item {{ $attributes }}>
    @if ($principle->type === \App\Enums\PrincipleType::Concept)
        <x-timeline.indicator color="blue">
            <flux:icon name="light-bulb" variant="micro" />
        </x-timeline.indicator>
    @else
        <x-timeline.indicator color="violet">
            <flux:icon name="scale" variant="micro" />
        </x-timeline.indicator>
    @endif

    @if ($editing)
        <x-timeline.content>
            <form wire:submit="updateText" class="space-y-4 rounded-xl border border-primary/40 bg-surface-container-lowest p-6">
                <flux:input label="Título" wire:model="textForm.title" placeholder="Ex: Sola Gratia" />
                <flux:error name="textForm.title" />

                <div wire:ignore>
                    <div x-data="markdownEditor('textForm.body')">
                        <textarea x-ref="textarea" placeholder="Princípio + comentários..."></textarea>
                    </div>
                </div>
                <flux:error name="textForm.body" />

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="cancelEditingText">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Salvar</flux:button>
                </div>
            </form>
        </x-timeline.content>
    @else
        <x-timeline.content class="group cursor-grab active:cursor-grabbing">
            <div class="flex items-start gap-3 rounded-xl border border-surface-variant bg-surface-container-lowest p-6">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        @if ($principle->type === \App\Enums\PrincipleType::Concept)
                            <flux:badge size="sm">Conceito</flux:badge>
                        @endif

                        @if ($principle->noteLinks->isNotEmpty())
                            <x-info-popover>
                                <p class="mb-2 font-semibold">Aplicado em:</p>
                                <ul class="space-y-2">
                                    @foreach ($principle->noteLinks as $link)
                                        <li>
                                            <a href="{{ route('disciplinas.show', ['slug' => $link->note->discipline->slug, 'nota' => $link->note_id]) }}" wire:navigate class="block text-primary hover:underline">
                                                {{ $link->note->title }}
                                            </a>
                                            <p class="text-xs text-on-surface-variant whitespace-pre-wrap">“{{ $link->snippet }}”</p>
                                        </li>
                                    @endforeach
                                </ul>
                            </x-info-popover>
                        @endif
                    </div>

                    @if ($principle->type === \App\Enums\PrincipleType::Concept)
                        <flux:heading size="lg" class="mt-2">{{ $principle->concept->term }}</flux:heading>
                        <p class="mt-2 text-on-surface-variant leading-relaxed whitespace-pre-wrap">{{ $principle->concept->definition }}</p>
                    @else
                        <flux:heading size="lg" class="mt-2">{{ $principle->title }}</flux:heading>
                        <div class="prose dark:prose-invert max-w-none mt-2 leading-relaxed">
                            {!! Str::markdownRich($principle->body) !!}
                        </div>
                    @endif

                    @if ($mastery !== null)
                        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-on-surface-variant">
                            <ul class="flex items-center gap-1" aria-label="Domínio por nível de estudo">
                                @foreach (\App\Enums\BloomLevel::cases() as $level)
                                    @php
                                        $value = $mastery['levels'][$level->value] ?? null;
                                        $state = $value === null ? 'ainda não praticado' : ($value >= 0.67 ? 'bem dominado' : ($value > 0 ? 'em andamento' : 'precisa de reforço'));
                                    @endphp
                                    <li class="size-3 rounded-full border border-outline {{ $value === null ? '' : ($value >= 0.67 ? 'border-primary bg-primary' : ($value > 0 ? 'border-primary bg-primary/40' : 'border-red-500 bg-red-500/30')) }}" title="{{ $level->label() }}: {{ $state }}">
                                        <span class="sr-only">{{ $level->label() }}: {{ $state }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <span>
                                @if ($mastery['dueAt'])
                                    {{ $mastery['dueAt']->isPast() ? 'Revisão vencida' : 'Próxima revisão: '.$mastery['dueAt']->translatedFormat('d \d\e M') }}
                                @else
                                    Ainda não estudado
                                @endif
                            </span>
                        </div>
                    @endif
                </div>

                <div class="flex shrink-0 flex-col gap-1 opacity-0 transition-opacity group-hover:opacity-100">
@if ($principle->type === \App\Enums\PrincipleType::Text)
                        <flux:button size="xs" variant="ghost" square icon="pencil"
                            wire:click="startEditingText({{ $principle->id }})" aria-label="Editar" />
                    @endif
                    <flux:button size="xs" variant="ghost" square icon="arrows-right-left"
                        wire:click="confirmMovePrinciple({{ $principle->id }})" aria-label="Mover para outro tema" />
                    <flux:button size="xs" variant="ghost" square icon="trash"
                        wire:click="confirmDeletePrinciple({{ $principle->id }})" aria-label="Remover" />
                </div>
            </div>
        </x-timeline.content>
    @endif
</x-timeline.item>
