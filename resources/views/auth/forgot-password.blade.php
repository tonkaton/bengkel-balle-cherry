<x-guest-layout title="Lupa Password">
    <h1 class="mb-4 text-xl font-semibold text-slate-900">Lupa Password</h1>
    <p class="mb-4 text-sm text-slate-500">
        Masukkan alamat email akun admin Anda. Kami akan mengirimkan tautan untuk membuat password baru.
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-slate-700 hover:underline">&larr; Kembali ke login</a>
            <x-primary-button>Kirim Tautan Reset</x-primary-button>
        </div>
    </form>
</x-guest-layout>
