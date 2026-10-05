<x-admin-layout title="Profil Saya">
    <div class="mx-auto max-w-xl space-y-6">
        <div class="kartu p-6">
            <h2 class="font-semibold text-slate-900">Informasi Akun</h2>
            <p class="mt-1 text-sm text-slate-500">Ubah nama dan alamat email akun admin Anda.</p>

            <form method="POST" action="{{ route('admin.profile.update') }}" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="name" value="Nama" />
                    <x-text-input id="name" name="name" class="mt-1 w-full" value="{{ old('name', $user->name) }}" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 w-full" value="{{ old('email', $user->email) }}" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <x-primary-button type="submit">Simpan Perubahan</x-primary-button>
            </form>
        </div>

        <div class="kartu p-6">
            <h2 class="font-semibold text-slate-900">Ubah Password</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan password yang panjang dan sulit ditebak.</p>

            <form method="POST" action="{{ route('password.update') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="current_password" value="Password Saat Ini" />
                    <x-text-input id="current_password" name="current_password" type="password" class="mt-1 w-full" autocomplete="current-password" />
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password" value="Password Baru" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
                </div>

                <x-primary-button type="submit">Ubah Password</x-primary-button>
            </form>
        </div>
    </div>
</x-admin-layout>
