{{--
    Satu kartu rekomendasi kecil di dalam kotak "Mungkin Anda Juga Butuh".
    Prop "item" adalah array ['item' => Produk|Servis, 'jenis' => 'produk'|'servis',
    'skor' => float, 'metode' => string] dari RekomendasiService - lihat komentar
    panjang di service itu soal kenapa skor di sini SELALU angka asli, tidak
    pernah dikarang. Sparepart bisa langsung ditambahkan ke keranjang,
    sedangkan servis diarahkan ke form booking.
--}}
@props(['item'])
@php($model = $item['item'])
@php($servis = $item['jenis'] === 'servis')

<div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3">
    <img src="{{ $model->gambar_url }}" alt="{{ $model->nama }}" class="h-12 w-12 shrink-0 rounded-md object-cover">

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-slate-900">{{ $model->nama }}</p>
        <p class="text-sm text-brand-700">{{ $model->harga_format }}@if ($servis) <span class="text-xs text-slate-500">&middot; jasa servis</span>@endif</p>
        <span class="badge mt-0.5 bg-slate-100 text-slate-600" title="{{ $item['metode'] === 'knn' ? 'Dihitung dari kemiripan pola rating (K-Nearest Neighbor)' : 'Dihitung dari frekuensi pembelian bersama' }}">
            {{ $item['skor'] }}% &middot; {{ $item['metode'] === 'knn' ? 'KNN' : 'Sering dibeli bersama' }}
        </span>
    </div>

    @if ($servis)
        <a href="{{ route('servis.booking', $model) }}" class="shrink-0 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-700" title="Booking servis ini">
            Booking
        </a>
    @elseif ($model->stok > 0)
        <form method="POST" action="{{ route('keranjang.tambah', $model) }}">
            @csrf
            <button type="submit" class="rounded-lg bg-slate-900 p-2 text-white transition hover:bg-slate-700" title="Tambah ke keranjang">
                <x-icon.plus class="h-4 w-4" />
            </button>
        </form>
    @else
        <span class="shrink-0 text-xs text-rose-500">Stok habis</span>
    @endif
</div>
