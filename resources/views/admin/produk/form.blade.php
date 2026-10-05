@php($sedangUbah = $produk->exists)

<x-admin-layout :title="$sedangUbah ? 'Ubah Produk' : 'Tambah Produk'">
    <div class="mx-auto max-w-2xl kartu p-6">
        <form method="POST"
            action="{{ $sedangUbah ? route('admin.produk.update', $produk) : route('admin.produk.store') }}"
            enctype="multipart/form-data" class="space-y-5">
            @csrf
            @if ($sedangUbah) @method('PUT') @endif

            <div class="flex items-center gap-4">
                <img src="{{ $produk->exists ? $produk->gambar_url : asset('images/produk-default.svg') }}" alt="" class="h-20 w-20 rounded-lg border border-slate-200 object-cover">
                <div class="flex-1">
                    <x-input-label for="gambar" value="Foto Produk" />
                    <input id="gambar" name="gambar" type="file" accept="image/*"
                        class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-slate-200">
                    <p class="mt-1 text-xs text-slate-400">JPG/PNG/WEBP, maks 2MB. Kosongkan jika tidak ingin mengganti.</p>
                    <x-input-error :messages="$errors->get('gambar')" class="mt-1" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="barcode" value="Barcode" />
                    <x-text-input id="barcode" name="barcode" class="mt-1" value="{{ old('barcode', $produk->barcode) }}" required autofocus />
                    <x-input-error :messages="$errors->get('barcode')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="kategori_id" value="Kategori" />
                    <select id="kategori_id" name="kategori_id" required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($kategori as $item)
                            <option value="{{ $item->id }}" @selected(old('kategori_id', $produk->kategori_id) == $item->id)>{{ $item->nama }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('kategori_id')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="nama" value="Nama Produk" />
                <x-text-input id="nama" name="nama" class="mt-1 w-full" value="{{ old('nama', $produk->nama) }}" required />
                <x-input-error :messages="$errors->get('nama')" class="mt-1" />
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="harga" value="Harga (Rp)" />
                    <x-text-input id="harga" name="harga" type="number" min="0" class="mt-1" value="{{ old('harga', $produk->harga) }}" required />
                    <x-input-error :messages="$errors->get('harga')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="stok" value="Stok" />
                    <x-text-input id="stok" name="stok" type="number" min="0" class="mt-1" value="{{ old('stok', $produk->stok) }}" required />
                    <x-input-error :messages="$errors->get('stok')" class="mt-1" />
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button type="submit">{{ $sedangUbah ? 'Simpan Perubahan' : 'Tambah Produk' }}</x-primary-button>
                <a href="{{ route('admin.produk.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Batal</a>
            </div>
        </form>
    </div>
</x-admin-layout>
