<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} - {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-slate-900 px-4 font-sans antialiased">

    <a href="{{ route('home') }}" class="mb-6 flex flex-col items-center gap-3">
        <x-application-logo class="h-16 w-16" />
        <span class="text-sm text-slate-400">Kembali ke halaman toko</span>
    </a>

    <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
        {{ $slot }}
    </div>
</body>
</html>
