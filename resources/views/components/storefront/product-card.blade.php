@props(['produk'])

<div class="group kartu flex flex-col overflow-hidden transition hover:shadow-md">
    <div class="aspect-square w-full overflow-hidden bg-slate-100">
        <img src="{{ $produk->gambar_url }}" alt="{{ $produk->nama }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
    </div>

    <div class="flex flex-1 flex-col gap-1 p-4">
        @if ($produk->kategori)
            <span class="badge w-fit bg-brand-50 text-brand-700">{{ $produk->kategori->nama }}</span>
        @endif

        <h3 class="font-medium text-slate-900" title="{{ $produk->nama }}">{{ $produk->nama }}</h3>
        <p class="text-lg font-semibold text-slate-900">{{ $produk->harga_format }}</p>
        <p class="text-xs text-slate-500">Stok: {{ $produk->stok }}</p>

        <form method="POST" action="{{ route('keranjang.tambah', $produk) }}" class="mt-auto pt-3">
            @csrf
            <button type="submit"
                @disabled($produk->stok_habis)
                class="flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition
                    {{ $produk->stok_habis ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-brand-600 text-white hover:bg-brand-700' }}">
                @if ($produk->stok_habis)
                    Stok Habis
                @else
                    <x-icon.plus class="h-4 w-4" /> Tambah
                @endif
            </button>
        </form>
    </div>
</div>
