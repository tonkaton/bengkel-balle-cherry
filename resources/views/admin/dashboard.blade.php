<x-admin-layout title="Dashboard">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat-card label="Total Produk" :value="$totalProduk" icon="box" accent="brand" />
        <x-admin.stat-card label="Total Transaksi" :value="$totalTransaksi" icon="chart" accent="emerald" />
        <x-admin.stat-card label="Total Pendapatan" value="Rp {{ number_format($totalPendapatan, 0, ',', '.') }}" icon="tag" accent="amber" />
        <x-admin.stat-card label="Rata-rata Rating" value="{{ $rataRating ?: '-' }} / 5 ({{ $jumlahRating }} ulasan)" icon="star" accent="rose" />
    </div>

    {{--
        Catatan penting: TIDAK ADA kartu "Akurasi Sistem" di dashboard ini.
        Versi PHP lama menampilkan klaim "Akurasi: 85-95%" yang ternyata
        cuma teks statis, tidak pernah dihitung dari apa pun (Laporan Audit
        Teknis temuan F06). Semua angka di halaman ini adalah hasil COUNT/SUM
        asli dari tabel database - lihat DashboardController.
    --}}

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="kartu p-5">
            <h2 class="mb-4 font-semibold text-slate-900">Stok Menipis</h2>
            @if ($produkStokMenipis->isEmpty())
                <p class="text-sm text-slate-500">Tidak ada produk dengan stok rendah saat ini.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($produkStokMenipis as $produk)
                        <li class="flex items-center justify-between py-2.5 text-sm">
                            <span class="text-slate-700">{{ $produk->nama }}</span>
                            <span class="badge bg-rose-50 text-rose-600">{{ $produk->stok }} unit</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('admin.analisis-stok.index') }}" class="mt-3 inline-block text-sm font-medium text-brand-600 hover:underline">Lihat analisis lengkap &rarr;</a>
            @endif
        </div>

        <div class="kartu p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Rating Terbaru</h2>
                <span class="text-xs text-slate-400">{{ $rekomendasiBulanIni }} rekomendasi dihasilkan bulan ini</span>
            </div>
            @if ($ratingTerbaru->isEmpty())
                <p class="text-sm text-slate-500">Belum ada rating dari pelanggan.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($ratingTerbaru as $rating)
                        <li class="py-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <x-icon.star :solid="$i <= $rating->rating" class="h-4 w-4 {{ $i <= $rating->rating ? 'text-amber-400' : 'text-slate-200' }}" />
                                    @endfor
                                </div>
                                <span class="text-xs text-slate-400">{{ $rating->tanggal->diffForHumans() }}</span>
                            </div>
                            @if ($rating->komen)
                                <p class="mt-1 truncate text-sm text-slate-600">&ldquo;{{ $rating->komen }}&rdquo;</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-admin-layout>
