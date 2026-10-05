{{--
    Satu kartu rekomendasi kecil di dalam kotak "Mungkin Anda Juga Butuh".
    Prop "item" adalah array ['produk' => Produk, 'skor' => float, 'metode' => string]
    dari RekomendasiService - lihat komentar panjang di service itu soal
    kenapa skor di sini SELALU angka asli, tidak pernah dikarang.
--}}
@props(['item'])
@php($produk = $item['produk'])

<div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3">
    <img src="{{ $produk->gambar_url }}" alt="{{ $produk->nama }}" class="h-12 w-12 shrink-0 rounded-md object-cover">

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-slate-900">{{ $produk->nama }}</p>
        <p class="text-sm text-brand-700">{{ $produk->harga_format }}</p>
        <span class="badge mt-0.5 bg-slate-100 text-slate-600" title="{{ $item['metode'] === 'knn' ? 'Dihitung dari kemiripan pola rating (K-Nearest Neighbor)' : 'Dihitung dari frekuensi pembelian bersama' }}">
            {{ $item['skor'] }}% &middot; {{ $item['metode'] === 'knn' ? 'KNN' : 'Sering dibeli bersama' }}
        </span>
    </div>

    <form method="POST" action="{{ route('keranjang.tambah', $produk) }}">
        @csrf
        <button type="submit" class="rounded-lg bg-slate-900 p-2 text-white transition hover:bg-slate-700" title="Tambah ke keranjang">
            <x-icon.plus class="h-4 w-4" />
        </button>
    </form>
</div>
