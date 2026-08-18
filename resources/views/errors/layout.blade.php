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
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-background text-on-background min-h-screen antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_right,var(--color-primary-container),transparent_60%)] opacity-20"></div>

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
