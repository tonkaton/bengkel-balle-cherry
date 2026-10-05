<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} - Admin {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans text-slate-900 antialiased" x-data="{ sidebarTerbuka: false }">

    <div class="flex min-h-screen">
        {{-- SIDEBAR --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-slate-900 transition-transform duration-200 lg:static lg:translate-x-0"
            :class="sidebarTerbuka ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center gap-3 px-5 py-5">
                <x-application-logo />
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-white">Balle Chery</p>
                    <p class="text-xs text-slate-400">Panel Admin</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2">
                <x-admin-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    <x-icon.home class="h-5 w-5" /> Dashboard
                </x-admin-nav-link>

                <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Katalog</p>
                <x-admin-nav-link :href="route('admin.produk.index')" :active="request()->routeIs('admin.produk.*')">
                    <x-icon.box class="h-5 w-5" /> Produk
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.kategori.index')" :active="request()->routeIs('admin.kategori.*')">
                    <x-icon.tag class="h-5 w-5" /> Kategori
                </x-admin-nav-link>

                <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Wawasan</p>
                <x-admin-nav-link :href="route('admin.analisis-stok.index')" :active="request()->routeIs('admin.analisis-stok.*')">
                    <x-icon.chart class="h-5 w-5" /> Analisis Popularitas
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.rekomendasi.index')" :active="request()->routeIs('admin.rekomendasi.*')">
                    <x-icon.sparkles class="h-5 w-5" /> Log Rekomendasi
                </x-admin-nav-link>
                <x-admin-nav-link :href="route('admin.rating.index')" :active="request()->routeIs('admin.rating.*')">
                    <x-icon.star class="h-5 w-5" /> Rating &amp; Komentar
                </x-admin-nav-link>

                <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Laporan</p>
                <x-admin-nav-link href="{{ route('admin.export.transaksi') }}">
                    <x-icon.download class="h-5 w-5" /> Unduh Transaksi (CSV)
                </x-admin-nav-link>
                <x-admin-nav-link href="{{ route('admin.export.produk') }}">
                    <x-icon.download class="h-5 w-5" /> Unduh Produk (CSV)
                </x-admin-nav-link>
            </nav>

            <div class="border-t border-slate-800 p-3">
                <x-admin-nav-link :href="route('admin.profile.edit')" :active="request()->routeIs('admin.profile.*')">
                    <x-icon.user class="h-5 w-5" /> {{ auth()->user()->name }}
                </x-admin-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white">
                        <x-icon.logout class="h-5 w-5" /> Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" x-show="sidebarTerbuka" x-cloak @click="sidebarTerbuka = false"></div>

        {{-- KONTEN --}}
        <div class="flex min-h-screen flex-1 flex-col lg:pl-0">
            <header class="sticky top-0 z-20 flex items-center gap-4 border-b border-slate-200 bg-white px-4 py-3 lg:px-8">
                <button type="button" class="text-slate-500 lg:hidden" @click="sidebarTerbuka = true">
                    <x-icon.menu class="h-6 w-6" />
                </button>
                <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                <a href="{{ route('home') }}" target="_blank" class="ml-auto text-sm font-medium text-brand-600 hover:text-brand-700">
                    Lihat Toko &rarr;
                </a>
            </header>

            <main class="flex-1 p-4 lg:p-8">
                <x-alert />
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
