<x-admin-layout title="Jasa Servis">
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Jasa servis dipesan pelanggan lewat booking, terpisah dari katalog sparepart.</p>
        <a href="{{ route('admin.servis.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
            <x-icon.plus class="h-4 w-4" /> Tambah Servis
        </a>
    </div>

    <div class="kartu overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-4 py-3">Servis</th>
                    <th class="px-4 py-3">Harga Jasa</th>
                    <th class="px-4 py-3">Estimasi</th>
                    <th class="px-4 py-3">Booking</th>
                    <th class="px-4 py-3">Dikerjakan</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($servis as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $item->gambar_url }}" alt="" class="h-10 w-10 rounded-md object-cover">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $item->nama }}</p>
                                    <p class="max-w-md truncate text-xs text-slate-500">{{ $item->deskripsi }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $item->harga_format }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->estimasi_format }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->booking_count }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->detail_transaksi_count }} kali</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.servis.edit', $item) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Ubah</a>
                                <form method="POST" action="{{ route('admin.servis.destroy', $item) }}" onsubmit="return confirm('Hapus {{ $item->nama }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada jasa servis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
