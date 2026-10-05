<x-admin-layout title="Log Rekomendasi">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-slate-500">Riwayat rekomendasi yang dihasilkan RekomendasiService. Kolom "Metode" menunjukkan jujur apakah hasilnya dari perhitungan KNN atau dari pola pembelian bersama.</p>
        <form method="POST" action="{{ route('admin.rekomendasi.perbarui') }}">
            @csrf
            <x-secondary-button type="submit">Perbarui Sekarang</x-secondary-button>
        </form>
    </div>

    <div class="kartu overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-4 py-3">Produk Asal</th>
                    <th class="px-4 py-3">Direkomendasikan</th>
                    <th class="px-4 py-3">Skor</th>
                    <th class="px-4 py-3">Metode</th>
                    <th class="px-4 py-3">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($log as $item)
                    <tr>
                        <td class="px-4 py-3 text-slate-700">{{ $item->produkAsal->nama ?? '-' }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $item->produkRekomendasi->nama ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->skor }}%</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $item->metode === 'knn' ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $item->metode === 'knn' ? 'K-Nearest Neighbor' : 'Sering dibeli bersama' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-400">{{ $item->tanggal->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada log rekomendasi. Klik "Perbarui Sekarang" untuk menghitungnya.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $log->links() }}</div>
</x-admin-layout>
