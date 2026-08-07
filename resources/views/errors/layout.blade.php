<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>
        @yield('code')
        -
        @yield('title')
    </title>

    <link rel="icon" href="/favicon.ico" sizes="any" />
    <link rel="icon" href="/favicon.svg" type="image/svg+xml" />

    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-background text-on-background min-h-screen antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_right,_var(--color-primary-container),_transparent_60%)] opacity-20"></div>

    <header class="bg-surface-container-lowest/80 border-outline-variant/20 sticky top-0 z-40 border-b backdrop-blur-md">
        <div class="mx-auto flex h-14 w-full max-w-7xl items-center px-6 lg:px-8">
            <a href="{{ route('dashboard') }}" class="text-primary me-4 flex h-10 items-center text-xl font-bold">
                Seminário
            </a>

            <nav class="max-md:hidden">
                <ul class="flex items-center gap-1">
                    <li>
                        <a
                            href="{{ route('dashboard') }}"
                            class="text-on-surface-variant hover:bg-surface-variant/40 hover:text-on-surface flex h-8 items-center rounded-lg px-3 text-sm font-medium transition-colors"
                        >
                            Disciplinas
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('dashboard') }}"
                            class="text-on-surface-variant hover:bg-surface-variant/40 hover:text-on-surface flex h-8 items-center rounded-lg px-3 text-sm font-medium transition-colors"
                        >
                            Lições Pastorais
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('dashboard') }}"
                            class="text-on-surface-variant hover:bg-surface-variant/40 hover:text-on-surface flex h-8 items-center rounded-lg px-3 text-sm font-medium transition-colors"
                        >
                            Referências
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="flex min-h-[calc(100vh-3.5rem)] items-center justify-center px-6 py-16">
        <div class="border-outline-variant/20 bg-surface-container-low w-full max-w-lg rounded-2xl border p-10 text-center shadow-sm sm:p-14">
            <p class="text-on-surface-variant text-sm font-semibold tracking-widest uppercase">@yield('title')</p>

            <p class="text-primary mt-2 text-7xl font-bold tracking-tight sm:text-8xl">@yield('code')</p>

            <p class="text-on-surface-variant mt-4 text-lg">@yield('message')</p>

            <a
                href="{{ route('dashboard') }}"
                class="bg-primary text-on-primary mt-8 inline-flex h-10 items-center justify-center rounded-lg px-5 text-sm font-semibold transition-opacity hover:opacity-90"
            >
                Voltar para o início
            </a>
        </div>
    </main>
</body>
</html>
