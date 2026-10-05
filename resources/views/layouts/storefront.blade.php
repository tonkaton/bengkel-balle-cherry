<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Belanja' }} - {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 antialiased">

    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <x-application-logo />
                <div class="leading-tight">
                    <p class="font-semibold text-slate-900">Balle Chery</p>
                    <p class="text-xs text-slate-500">Sparepart Kendaraan Bermotor</p>
                </div>
            </a>

            <div class="ml-auto flex items-center gap-3">
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                        Panel Admin
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Login Admin
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 lg:px-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-sm text-slate-500">
        &copy; {{ now()->year }} Bengkel Balle Chery. Dibangun dengan Laravel.
    </footer>
</body>
</html>
