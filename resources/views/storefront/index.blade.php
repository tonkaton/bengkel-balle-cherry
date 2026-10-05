<x-storefront-layout title="Belanja Sparepart">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[220px_1fr_320px]">

        {{-- SIDEBAR KATEGORI --}}
        <aside class="order-2 lg:order-1">
            <div class="kartu sticky top-20 p-4">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Kategori</h2>
                <nav class="space-y-1">
                    <a href="{{ route('home', ['cari' => $kataKunci]) }}"
                        class="block rounded-lg px-3 py-2 text-sm {{ ! $kategoriAktif ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                        Semua Produk
                    </a>
                    @foreach ($kategori as $item)
                        <a href="{{ route('home', ['kategori' => $item->id, 'cari' => $kataKunci]) }}"
                            class="flex items-center justify-between rounded-lg px-3 py-2 text-sm {{ $kategoriAktif === $item->id ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                            <span>{{ $item->nama }}</span>
                            <span class="text-xs text-slate-400">{{ $item->produk_count }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        {{-- KATALOG PRODUK --}}
        <div class="order-1 lg:order-2">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row">
                <form method="POST" action="{{ route('keranjang.scan') }}" class="flex flex-1 gap-2">
                    @csrf
                    <div class="relative flex-1">
                        <x-icon.search class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input type="text" name="barcode" placeholder="Scan atau ketik barcode, lalu Enter..." autofocus
                            class="w-full rounded-lg border-slate-300 pl-10 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <x-secondary-button type="submit">Cari</x-secondary-button>
                </form>

                <form method="GET" action="{{ route('home') }}" class="flex flex-1 gap-2">
                    @if ($kategoriAktif)
                        <input type="hidden" name="kategori" value="{{ $kategoriAktif }}">
                    @endif
                    <input type="text" name="cari" value="{{ $kataKunci }}" placeholder="Cari nama produk..."
                        class="w-full flex-1 rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <x-secondary-button type="submit">Filter</x-secondary-button>
                </form>
            </div>

            @if ($produk->isEmpty())
                <div class="kartu p-10 text-center text-slate-500">
                    Belum ada produk yang cocok. Coba kata kunci lain, atau
                    @auth
                        <a href="{{ route('admin.produk.create') }}" class="text-brand-600 hover:underline">tambahkan produk baru</a>.
                    @else
                        hubungi admin untuk menambahkan produk.
                    @endauth
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($produk as $item)
                        <x-storefront.product-card :produk="$item" />
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $produk->links() }}
                </div>
            @endif
        </div>

        {{-- KERANJANG & REKOMENDASI --}}
        <aside class="order-3">
            <div class="sticky top-20 space-y-4">
                <div class="kartu p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <x-icon.cart class="h-5 w-5" /> Keranjang
                        </h2>
                        @if ($keranjang->isNotEmpty())
                            <form method="POST" action="{{ route('keranjang.kosongkan') }}" onsubmit="return confirm('Kosongkan keranjang?')">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-rose-600 hover:underline">Kosongkan</button>
                            </form>
                        @endif
                    </div>

                    @if ($keranjang->isEmpty())
                        <p class="py-6 text-center text-sm text-slate-400">Keranjang masih kosong.</p>
                    @else
                        <ul class="max-h-72 space-y-3 overflow-y-auto pr-1">
                            @foreach ($keranjang as $item)
                                <li class="flex items-center gap-3">
                                    <img src="{{ $item['produk']->gambar_url }}" class="h-10 w-10 rounded-md object-cover" alt="">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-slate-900">{{ $item['produk']->nama }}</p>
                                        <p class="text-xs text-slate-500">{{ $item['produk']->harga_format }} &times; {{ $item['jumlah'] }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('keranjang.hapus', $item['produk']) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-rose-600" title="Hapus">
                                            <x-icon.trash class="h-4 w-4" />
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-3">
                            <span class="text-sm text-slate-600">Total</span>
                            <span class="text-lg font-semibold text-slate-900">Rp {{ number_format($totalKeranjang, 0, ',', '.') }}</span>
                        </div>

                        <form method="POST" action="{{ route('checkout.store') }}" class="mt-3">
                            @csrf
                            <x-primary-button type="submit" class="w-full">Checkout</x-primary-button>
                        </form>
                    @endif
                </div>

                @if ($rekomendasi->isNotEmpty())
                    <div class="kartu p-4">
                        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <x-icon.sparkles class="h-5 w-5 text-brand-600" /> Mungkin Anda Juga Butuh
                        </h2>
                        <div class="space-y-2">
                            @foreach ($rekomendasi as $item)
                                <x-storefront.rekomendasi-card :item="$item" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</x-storefront-layout>
