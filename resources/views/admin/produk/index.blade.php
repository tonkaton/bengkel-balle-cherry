<x-admin-layout title="Produk">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-1 gap-2 sm:max-w-sm">
            <input type="text" name="cari" value="{{ $kataKunci }}" placeholder="Cari nama atau barcode..."
                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            <x-secondary-button type="submit">Cari</x-secondary-button>
        </form>

        <a href="{{ route('admin.produk.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
            <x-icon.plus class="h-4 w-4" /> Tambah Produk
        </a>
    </div>

    <div class="kartu overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3">Barcode</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Harga</th>
                    <th class="px-4 py-3">Stok</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($produk as $item)
                    <tr>
                        <td class="flex items-center gap-3 px-4 py-3">
                            <img src="{{ $item->gambar_url }}" alt="" class="h-10 w-10 rounded-md object-cover">
                            <span class="font-medium text-slate-900">{{ $item->nama }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->barcode }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->kategori->nama ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $item->harga_format }}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $item->stok <= 5 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' }}">{{ $item->stok }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.produk.edit', $item) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Ubah</a>
                                <form method="POST" action="{{ route('admin.produk.destroy', $item) }}" onsubmit="return confirm('Hapus {{ $item->nama }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada produk yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $produk->links() }}</div>
</x-admin-layout>
