{{-- Índice (TOC) colapsável gerado dos headings "#" a "###" do Markdown.
     Espera $headings de App\Support\MarkdownToc::headings(); sem headings
     suficientes a lista vem vazia e nada é desenhado. Alpine em vez de
     <details>: o Livewire fecha o <details> a cada re-render. --}}
@props(['headings' => []])

@if (count($headings) > 0)
    <nav x-data="{ open: false }" aria-label="Índice da anotação" {{ $attributes->class('rounded-lg border border-surface-variant bg-surface-container-low') }}>
        <button
            type="button"
            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-start text-sm font-medium"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open"
        >
            <span class="flex items-center gap-2">
                <flux:icon name="list-bullet" class="size-4" />
                Índice ({{ count($headings) }})
            </span>
            <flux:icon name="chevron-down" class="size-4 transition-transform" x-bind:class="open && 'rotate-180'" />
        </button>

        <ol x-show="open" x-cloak class="space-y-1 border-t border-surface-variant px-3 py-2 text-sm">
            @foreach ($headings as $heading)
                <li style="padding-inline-start: {{ ($heading['level'] - 1) * 1 }}rem">
                    <a href="#{{ $heading['id'] }}" class="text-on-surface-variant hover:text-primary break-words underline-offset-2 hover:underline">
                        {{ $heading['text'] }}
                    </a>
                </li>
            @endforeach
        </ol>
    </nav>
@endif
