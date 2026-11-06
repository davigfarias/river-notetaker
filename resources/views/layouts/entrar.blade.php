@php
    $modulos = [
        ['icon' => 'squares-2x2', 'titulo' => 'Disciplinas', 'texto' => 'Anotações organizadas por matéria.'],
        ['icon' => 'light-bulb', 'titulo' => 'Conceitos', 'texto' => 'Ideias-chave com revisão espaçada.'],
        ['icon' => 'scale', 'titulo' => 'Princípios', 'texto' => 'Seus princípios reunidos para consulta.'],
        ['icon' => 'users', 'titulo' => 'Conselhos Pastorais', 'texto' => 'Conselhos reunidos para consulta.'],
        ['icon' => 'book-open', 'titulo' => 'Referências', 'texto' => 'Obras e autores, com mapa mental.'],
        ['icon' => 'clock', 'titulo' => 'Histórico', 'texto' => 'Linha do tempo de eventos da História, em ordem cronológica.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ filled($title ?? null) && $title !== config('app.name') ? $title.' - '.config('app.name') : config('app.name') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

    {{-- Mesmo tema salvo do layout principal (aqui não há botão de alternar). Padrão: dark. --}}
    <script>
        if ((localStorage.getItem('theme') ?? 'dark') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="h-dvh overflow-hidden flex flex-col bg-background text-on-background antialiased">

<div class="flex flex-1 min-h-0 flex-col overflow-y-auto lg:grid lg:grid-cols-2 lg:overflow-hidden">

    {{-- Esquerda: logo, nome e informações. No celular sobra só logo + nome. --}}
    <section class="flex shrink-0 flex-col justify-center gap-8 bg-surface-container-low px-6 py-8 lg:px-16 lg:py-12">
        <div class="flex items-center gap-3">
            <img src="/img/river.png" alt="Logo do Notetaker" class="size-12 rounded-xl" />
            <span class="text-2xl font-semibold">Notetaker</span>
        </div>

        <p class="hidden max-w-md text-on-surface-variant lg:block">
            Seu caderno pessoal de estudos: organize anotações, conceitos e reflexões por disciplina e revise no tempo certo.
        </p>

        <div class="hidden max-w-xl gap-3 sm:grid-cols-2 lg:grid">
            @foreach ($modulos as $modulo)
                <div class="flex items-start gap-3 rounded-xl border border-outline-variant/30 bg-surface-container-lowest p-4">
                    <flux:icon :name="$modulo['icon']" class="mt-0.5 size-5 shrink-0 text-primary" />
                    <div>
                        <div class="text-sm font-semibold">{{ $modulo['titulo'] }}</div>
                        <div class="text-sm text-on-surface-variant">{{ $modulo['texto'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Direita: PIN --}}
    <main class="flex flex-1 items-center justify-center px-6 py-10">
        {{ $slot }}
    </main>

</div>

<footer class="hidden h-14 shrink-0 md:flex">
    <div class="mx-auto flex h-full items-center justify-center gap-2 px-6 text-sm text-on-surface-variant">
        <span>Desenvolvido orgulhosamente em</span>

        <span class="flex items-center gap-1 font-semibold text-red-500">
            <img src="https://upload.wikimedia.org/wikipedia/commons/9/9a/Laravel.svg" alt="Laravel" class="h-4 w-4">
            Laravel
        </span>

        <span>por</span>

        <a href="https://github.com/DaveFarias" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 font-semibold hover:opacity-80">
            <img src="https://uxwing.com/wp-content/themes/uxwing/download/brands-and-social-media/github-white-icon.png" alt="GitHub" class="h-4 w-4">
            Dave Farias
        </a>
    </div>
</footer>

@persist('toast')
<flux:toast position="bottom center" />
@endpersist

{{-- Igual ao layout principal: o dashboard (aberto via wire:navigate) precisa deste script. --}}
<script src="{{ route('livewire-autocomplete.asset', 'autocomplete.js') }}"></script>

@livewireScripts
@fluxScripts

</body>
</html>
