@php($sedangUbah = $servis->exists)

<x-admin-layout :title="$sedangUbah ? 'Ubah Servis' : 'Tambah Servis'">
    <div class="mx-auto max-w-2xl kartu p-6">
        <form method="POST"
            action="{{ $sedangUbah ? route('admin.servis.update', $servis) : route('admin.servis.store') }}"
            enctype="multipart/form-data" class="space-y-5">
            @csrf
            @if ($sedangUbah) @method('PUT') @endif

            <div class="flex items-center gap-4">
                <img src="{{ $servis->gambar_url }}" alt="" class="h-20 w-20 rounded-lg border border-slate-200 object-cover">
                <div class="flex-1">
                    <x-input-label for="gambar" value="Gambar Servis" />
                    <input id="gambar" name="gambar" type="file" accept="image/*"
                        class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-slate-200">
                    <p class="mt-1 text-xs text-slate-400">JPG/PNG/WEBP, maks 2MB. Kosongkan jika tidak ingin mengganti.</p>
                    <x-input-error :messages="$errors->get('gambar')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="nama" value="Nama Servis" />
                <x-text-input id="nama" name="nama" class="mt-1 w-full" value="{{ old('nama', $servis->nama) }}" required autofocus />
                <x-input-error :messages="$errors->get('nama')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="deskripsi" value="Pekerjaan yang Dilakukan" />
                <textarea id="deskripsi" name="deskripsi" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('deskripsi', $servis->deskripsi) }}</textarea>
                <x-input-error :messages="$errors->get('deskripsi')" class="mt-1" />
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="harga" value="Harga Jasa (Rp)" />
                    <x-text-input id="harga" name="harga" type="number" min="0" class="mt-1" value="{{ old('harga', $servis->harga) }}" required />
                    <x-input-error :messages="$errors->get('harga')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="estimasi_menit" value="Estimasi Pengerjaan (menit)" />
                    <x-text-input id="estimasi_menit" name="estimasi_menit" type="number" min="15" max="600" step="15" class="mt-1" value="{{ old('estimasi_menit', $servis->estimasi_menit) }}" required />
                    <x-input-error :messages="$errors->get('estimasi_menit')" class="mt-1" />
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button type="submit">{{ $sedangUbah ? 'Simpan Perubahan' : 'Tambah Servis' }}</x-primary-button>
                <a href="{{ route('admin.servis.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Batal</a>
            </div>
        </form>
    </div>
</x-admin-layout>
