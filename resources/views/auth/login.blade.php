<x-guest-layout title="Masuk">
    <h1 class="mb-1 text-xl font-semibold text-slate-900">Masuk ke Panel Admin</h1>
    <p class="mb-6 text-sm text-slate-500">Bengkel Balle Chery</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500">
            <span class="text-sm text-slate-600">Ingat saya</span>
        </label>

        <div class="flex items-center justify-between pt-2">
            @if (Route::has('password.request'))
                <a class="text-sm text-slate-500 hover:text-slate-700 hover:underline" href="{{ route('password.request') }}">
                    Lupa password?
                </a>
            @endif

            <x-primary-button>Masuk</x-primary-button>
        </div>
    </form>
</x-guest-layout>
