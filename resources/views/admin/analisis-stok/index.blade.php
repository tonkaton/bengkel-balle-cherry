{{--
    Dulu bernama "Analisis Stok dengan KNN" - lihat catatan lengkap soal
    kenapa namanya diluruskan jadi "Analisis Popularitas Produk" di
    app/Services/AnalisisStokService.php (Laporan Audit Teknis temuan F08).
    Ini aturan ambang batas penjualan, BUKAN algoritma machine learning.
--}}
<x-admin-layout title="Analisis Popularitas Produk">
    <div class="kartu mb-6 border-l-4 border-l-brand-500 p-5">
        <h2 class="font-semibold text-slate-900">Bagaimana halaman ini bekerja?</h2>
        <p class="mt-1 text-sm text-slate-600">
            Produk dikelompokkan berdasarkan total unit terjual memakai aturan ambang batas tetap (bukan algoritma
            machine learning). Semakin tinggi penjualan, semakin besar rekomendasi penambahan stoknya.
        </p>
        <ul class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 text-xs text-slate-500 sm:grid-cols-3">
            <li>&ge;50 unit &rarr; Sangat Laris (stok &times;3)</li>
            <li>&ge;20 unit &rarr; Laris (stok &times;2)</li>
            <li>&ge;10 unit &rarr; Cukup Laris (stok &times;1.5)</li>
            <li>&ge;5 unit &rarr; Normal (stok &times;1.2)</li>
            <li>&lt;5 unit &rarr; Sepi (stok &times;0.5)</li>
        </ul>
    </div>

    <div class="kartu overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3">Total Terjual</th>
                    <th class="px-4 py-3">Stok Saat Ini</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Rekomendasi Stok</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php
                $warnaStatus = [
                    'Sangat Laris' => 'bg-rose-50 text-rose-600',
                    'Laris' => 'bg-amber-50 text-amber-600',
                    'Cukup Laris' => 'bg-emerald-50 text-emerald-600',
                    'Normal' => 'bg-slate-100 text-slate-600',
                    'Sepi' => 'bg-slate-100 text-slate-400',
                ];
                @endphp
                @forelse ($analisis as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $item->nama }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ number_format($item->total_terjual) }} unit</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->stok }} unit</td>
                        <td class="px-4 py-3"><span class="badge {{ $warnaStatus[$item->status] }}">{{ $item->status }}</span></td>
                        <td class="px-4 py-3 font-medium text-brand-700">{{ $item->stok_rekomendasi }} unit</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.analisis-stok.update', $item->id) }}" onsubmit="return confirm('Update stok menjadi {{ $item->stok_rekomendasi }} unit?')">
                                @csrf
                                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">Terapkan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada data transaksi untuk dianalisis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
