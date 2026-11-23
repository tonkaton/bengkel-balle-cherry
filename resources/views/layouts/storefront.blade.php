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
        @php($menu = [
            ['Sparepart', route('home'), request()->routeIs('home')],
            ['Servis & Booking', route('servis.index'), request()->routeIs('servis.*', 'booking.*')],
        ])
        <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 lg:px-8">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
                <x-application-logo class="shrink-0" />
                <div class="min-w-0 leading-tight">
                    <p class="truncate font-semibold text-slate-900">Balle Chery</p>
                    <p class="hidden text-xs text-slate-500 sm:block">Sparepart Kendaraan Bermotor</p>
                </div>
            </a>

            <nav class="ml-4 hidden items-center gap-1 sm:flex">
                @foreach ($menu as [$label, $url, $aktif])
                    <a href="{{ $url }}" class="rounded-lg px-3 py-2 text-sm font-medium {{ $aktif ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="ml-auto shrink-0">
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-800 sm:px-4">
                        <span class="sm:hidden">Admin</span><span class="hidden sm:inline">Panel Admin</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:px-4">
                        <span class="sm:hidden">Admin</span><span class="hidden sm:inline">Login Admin</span>
                    </a>
                @endauth
            </div>
        </div>

        {{-- Menu tab untuk layar HP: lebar penuh, mudah disentuh. --}}
        <nav class="grid grid-cols-2 border-t border-slate-200 sm:hidden">
            @foreach ($menu as [$label, $url, $aktif])
                <a href="{{ $url }}" class="py-2.5 text-center text-sm font-medium {{ $aktif ? 'border-b-2 border-brand-600 text-brand-700' : 'text-slate-600' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-4 sm:py-6 lg:px-8">
        <x-alert />
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-sm text-slate-500">
        &copy; {{ now()->year }} Bengkel Balle Chery. Dibangun dengan Laravel.
    </footer>
</body>
</html>
