@php
    $navItems = [
        ['route' => 'dashboard', 'icon' => 'squares-2x2', 'label' => 'Disciplinas', 'active' => ['dashboard']],
        ['route' => 'concepts', 'icon' => 'light-bulb', 'label' => 'Conceitos', 'active' => ['concepts*']],
        ['route' => 'principios', 'icon' => 'scale', 'label' => 'Princípios', 'active' => ['principios*']],
        ['route' => 'pastoral', 'icon' => 'users', 'label' => 'Conselhos Pastorais', 'mobileLabel' => 'Conselhos', 'active' => ['pastoral']],
        ['route' => 'referencias', 'icon' => 'book-open', 'label' => 'Referências', 'active' => ['referencias*', 'referencias.show']],
        ['route' => 'historico', 'icon' => 'clock', 'label' => 'Histórico', 'active' => ['historico']],
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

    {{-- Aplica o tema salvo antes do CSS carregar, evitando flash do tema errado. Padrão: dark. --}}
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

<div class="pointer-events-none fixed inset-0 -z-10 hidden bg-[radial-gradient(ellipse_at_top_right,var(--color-primary-container),transparent_60%)] opacity-20 dark:block"></div>

<flux:header
    container
    sticky
    class="z-40 border-b border-outline-variant/20 bg-surface-container-lowest/80 backdrop-blur-md"
>
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" aria-label="Abrir menu" />

    <flux:brand href="{{ route('dashboard') }}" logo="/img/river.png" name="Notetaker" />

    <flux:navbar class="max-lg:hidden max-xl:**:data-flux-icon:hidden">

        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}" wire:navigate class="contents">
                <flux:navbar.item
                    :icon="$item['icon']"
                    :current="request()->routeIs(...$item['active'])"
                >
                    {{ $item['label'] }}
                </flux:navbar.item>
            </a>
        @endforeach

    </flux:navbar>

    <flux:spacer />

    <div class="flex items-center gap-2">
        <div class="hidden dark:block">
            <flux:button
                x-data
                x-on:click="
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                "
                variant="ghost"
                icon="sun"
                size="sm"
                aria-label="Alternar para modo claro"
            />
        </div>
        <div class="block dark:hidden">
            <flux:button
                x-data
                x-on:click="
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                "
                variant="ghost"
                icon="moon"
                size="sm"
                aria-label="Alternar para modo escuro"
            />
        </div>

        <livewire:busca-global />

        {{ $headerActions ?? '' }}
    </div>

</flux:header>

{{-- Menu do celular e tablet: abre pelo sanduíche do header. Só existe abaixo de lg. --}}
<flux:sidebar collapsible="mobile" class="lg:hidden border-e border-outline-variant/20 bg-surface-container-lowest">
    {{-- O header da página (z-40) cobre o topo do painel: o sanduíche dele abre e fecha. --}}
    <flux:sidebar.nav class="pt-14">
        @foreach ($navItems as $item)
            <flux:sidebar.item
                :href="route($item['route'])"
                :icon="$item['icon']"
                :current="request()->routeIs(...$item['active'])"
                wire:navigate
            >
                {{ $item['label'] }}
            </flux:sidebar.item>
        @endforeach
    </flux:sidebar.nav>
</flux:sidebar>

<main x-data x-mermaid class="flex-1 min-h-0 overflow-y-auto">
    <flux:main container class="min-h-full">
        {{ $slot }}
    </flux:main>
</main>

<footer class="h-14 shrink-0 hidden md:flex">
    <div class="mx-auto flex h-full items-center justify-center gap-2 px-6 text-sm text-on-surface-variant">

        <span>Desenvolvido orgulhosamente em</span>

        <span class="flex items-center gap-1 font-semibold text-red-500">
                <img
                    src="https://upload.wikimedia.org/wikipedia/commons/9/9a/Laravel.svg"
                    alt="Laravel"
                    class="h-4 w-4"
                >
                Laravel
            </span>

        <span>por</span>

        <a
            href="https://github.com/DaveFarias"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center gap-1 font-semibold hover:opacity-80"
        >
            <img
                src="https://uxwing.com/wp-content/themes/uxwing/download/brands-and-social-media/github-white-icon.png"
                alt="GitHub"
                class="h-4 w-4"
            >
            Dave Farias
        </a>

    </div>
</footer>

{{-- Fica antes do @fluxScripts: depois dele, o <ui-close> monta assim que o
     parser abre a tag, antes do <button> filho existir, e o Flux quebra com
     "Cannot read properties of null (reading 'addEventListener')". --}}
<flux:modal name="global-token-expiration" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Sessão finalizada</flux:heading>
            <flux:text class="mt-2">
                Sua sessão expirou. É preciso recarregar a página e refazer o login.
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Manter na página</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" x-on:click="window.location.reload()">
                Recarregar e entrar
            </flux:button>
        </div>
    </div>
</flux:modal>

@persist('toast')
<flux:toast position="bottom center" />
@endpersist

{{-- livewire-autocomplete: carregado globalmente (antes de @livewireScripts) para
     que <x-lwa::autocomplete> também funcione em páginas #[Lazy]. --}}
<script src="{{ route('livewire-autocomplete.asset', 'autocomplete.js') }}"></script>

@livewireScripts
@fluxScripts

</body>
</html>
