<x-guest-layout title="Konfirmasi Password">
    <p class="mb-4 text-sm text-slate-500">
        Ini adalah area sensitif aplikasi. Mohon konfirmasi password Anda sebelum melanjutkan.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end pt-2">
            <x-primary-button>Konfirmasi</x-primary-button>
        </div>
    </form>
</x-guest-layout>
