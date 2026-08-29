<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="h-screen overflow-hidden flex flex-col bg-background text-on-background antialiased">

<div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_right,var(--color-primary-container),transparent_60%)] opacity-20"></div>

<flux:header
    container
    sticky
    class="z-40 border-b border-outline-variant/20 bg-surface-container-lowest/80 backdrop-blur-md"
>
    <flux:brand href="{{ route('dashboard') }}" logo="/img/river.png" name="Notetaker" />

    <flux:navbar class="max-md:hidden">

        <a href="{{ route('dashboard') }}" wire:navigate class="contents">
            <flux:navbar.item
                icon="squares-2x2"
                :current="request()->routeIs('dashboard')"
            >
                Disciplinas
            </flux:navbar.item>
        </a>

        <a href="{{ route('concepts') }}" wire:navigate class="contents">
            <flux:navbar.item
                icon="light-bulb"
                :current="request()->routeIs('concepts')"
            >
                Conceitos
            </flux:navbar.item>
        </a>

        <a href="{{ route('pastoral') }}" wire:navigate class="contents">
            <flux:navbar.item
                icon="users"
                :current="request()->routeIs('pastoral')"
            >
                Conselhos Pastorais
            </flux:navbar.item>
        </a>

        <a href="{{ route('referencias') }}" wire:navigate class="contents">
            <flux:navbar.item
                icon="book-open"
                :current="request()->routeIs('referencias*')"
            >
                Referências
            </flux:navbar.item>
        </a>

    </flux:navbar>

    <flux:spacer />

    <div class="flex items-center gap-2">
        {{ $headerActions ?? '' }}
    </div>

</flux:header>

<main class="flex-1 min-h-0 overflow-y-auto">
    <flux:main class="min-h-full">
        {{ $slot }}
    </flux:main>
</main>

<footer class="h-14 shrink-0">
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

@persist('toast')
<flux:toast />
@endpersist

@livewireScripts
@fluxScripts

</body>
</html>
