{{--
    Halaman kelola kategori. Fitur ini tidak ada sama sekali di versi PHP
    lama - lihat catatan lengkap di Admin\KategoriController soal kenapa
    ditambahkan.
--}}
<x-admin-layout title="Kategori">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="kartu p-5 lg:col-span-1">
            <h2 class="mb-3 font-semibold text-slate-900">Tambah Kategori</h2>
            <form method="POST" action="{{ route('admin.kategori.store') }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="nama" value="Nama Kategori" />
                    <x-text-input id="nama" name="nama" class="mt-1 w-full" required autofocus />
                    <x-input-error :messages="$errors->get('nama')" class="mt-1" />
                </div>
                <x-primary-button type="submit" class="w-full">Simpan</x-primary-button>
            </form>
        </div>

        <div class="kartu overflow-hidden lg:col-span-2">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-4 py-3">Nama Kategori</th>
                        <th class="px-4 py-3">Jumlah Produk</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($kategori as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $item->nama }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $item->produk_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.kategori.destroy', $item) }}" onsubmit="return confirm('Hapus kategori {{ $item->nama }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10 text-center text-slate-400">Belum ada kategori.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
