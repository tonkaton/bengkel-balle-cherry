<x-storefront-layout title="Belanja Sparepart">
    @php($kategoriTerpilih = $kategori->firstWhere('id', $kategoriAktif))
    @php($jumlahItem = $keranjang->sum('jumlah'))

    {{--
        Tata letak: 1 kolom di HP, 3 kolom di desktop (kategori | katalog | keranjang).
        minmax(0,1fr) + min-w-0 wajib ada: tanpa itu baris Produk Unggulan yang bisa
        digeser ke samping akan melebarkan kolom tengah dan mendorong keranjang keluar layar.
    --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[200px_minmax(0,1fr)_300px] xl:grid-cols-[220px_minmax(0,1fr)_320px]">

        {{-- SIDEBAR KATEGORI (desktop) --}}
        <aside class="hidden lg:block">
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

        {{-- KOLOM TENGAH --}}
        <div class="min-w-0 space-y-4">
            {{-- Pencarian: barcode & nama --}}
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <form method="POST" action="{{ route('keranjang.scan') }}" class="flex gap-2">
                    @csrf
                    <div class="relative min-w-0 flex-1">
                        <x-icon.search class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input type="text" name="barcode" placeholder="Scan / ketik barcode" autofocus
                            class="w-full rounded-lg border-slate-300 pl-10 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <x-secondary-button type="submit" class="shrink-0">Cari</x-secondary-button>
                </form>

                <form method="GET" action="{{ route('home') }}" class="flex gap-2">
                    @if ($kategoriAktif)
                        <input type="hidden" name="kategori" value="{{ $kategoriAktif }}">
                    @endif
                    <input type="text" name="cari" value="{{ $kataKunci }}" placeholder="Cari nama produk"
                        class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <x-secondary-button type="submit" class="shrink-0">Filter</x-secondary-button>
                </form>
            </div>

            {{-- Kategori (HP): chip yang bisa digeser --}}
            <nav class="geser -mx-4 flex gap-2 overflow-x-auto px-4 lg:hidden" aria-label="Kategori">
                <a href="{{ route('home', ['cari' => $kataKunci]) }}"
                    class="shrink-0 whitespace-nowrap rounded-full border px-3 py-1.5 text-sm {{ ! $kategoriAktif ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white text-slate-600' }}">
                    Semua
                </a>
                @foreach ($kategori as $item)
                    <a href="{{ route('home', ['kategori' => $item->id, 'cari' => $kataKunci]) }}"
                        class="shrink-0 whitespace-nowrap rounded-full border px-3 py-1.5 text-sm {{ $kategoriAktif === $item->id ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white text-slate-600' }}">
                        {{ $item->nama }} <span class="opacity-70">({{ $item->produk_count }})</span>
                    </a>
                @endforeach
            </nav>

            {{-- PRODUK UNGGULAN: peringkat penjualan (bukan KNN); produk baru ikut tampil di akhir. --}}
            @if ($unggulan->isNotEmpty())
                <section class="kartu p-4" x-data>
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Produk Unggulan</h2>
                            <p class="text-xs text-slate-500">Diurutkan dari yang paling laku.</p>
                        </div>
                        <div class="hidden shrink-0 gap-1 sm:flex">
                            <button type="button" @click="$refs.baris.scrollBy({ left: -$refs.baris.clientWidth * 0.8, behavior: 'smooth' })"
                                class="rounded-full border border-slate-300 bg-white px-2.5 py-1 text-slate-600 hover:bg-slate-50" aria-label="Geser ke kiri">&lsaquo;</button>
                            <button type="button" @click="$refs.baris.scrollBy({ left: $refs.baris.clientWidth * 0.8, behavior: 'smooth' })"
                                class="rounded-full border border-slate-300 bg-white px-2.5 py-1 text-slate-600 hover:bg-slate-50" aria-label="Geser ke kanan">&rsaquo;</button>
                        </div>
                    </div>
                    <div x-ref="baris" class="geser -mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-px-4 px-4 pb-1">
                        @foreach ($unggulan as $item)
                            <article class="flex w-36 shrink-0 snap-start flex-col overflow-hidden rounded-lg border border-slate-200 bg-white sm:w-40">
                                <div class="relative aspect-square bg-white">
                                    <img src="{{ $item->gambar_url }}" alt="{{ $item->nama }}" class="h-full w-full object-contain p-2" loading="lazy">
                                    <span class="absolute left-2 top-2 rounded-md bg-slate-900/80 px-1.5 py-0.5 text-[11px] font-semibold text-white">#{{ $loop->iteration }}</span>
                                    @if ($item->terjual === 0)
                                        <span class="absolute right-2 top-2 rounded-md bg-emerald-600 px-1.5 py-0.5 text-[11px] font-semibold text-white">Baru</span>
                                    @endif
                                </div>
                                <div class="flex flex-1 flex-col gap-0.5 border-t border-slate-100 p-2.5">
                                    <h3 class="line-clamp-2 min-h-[2.5rem] text-sm font-medium leading-5 text-slate-900" title="{{ $item->nama }}">{{ $item->nama }}</h3>
                                    <p class="text-sm font-semibold text-slate-900">{{ $item->harga_format }}</p>
                                    <p class="text-xs text-slate-500">{{ $item->terjual > 0 ? 'Terjual '.$item->terjual : 'Belum ada penjualan' }}</p>
                                    <form method="POST" action="{{ route('keranjang.tambah', $item) }}" class="mt-auto pt-2">
                                        @csrf
                                        <button type="submit" @disabled($item->stok_habis)
                                            class="flex w-full items-center justify-center gap-1 rounded-md px-2 py-1.5 text-xs font-semibold transition {{ $item->stok_habis ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-brand-600 text-white hover:bg-brand-700' }}">
                                            @if ($item->stok_habis)
                                                Stok Habis
                                            @else
                                                <x-icon.plus class="h-3.5 w-3.5" /> Tambah
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- KATALOG --}}
            <div class="flex items-baseline justify-between">
                <h2 class="text-base font-semibold text-slate-900">
                    @if ($kataKunci !== '')
                        Hasil pencarian "{{ $kataKunci }}"
                    @else
                        {{ $kategoriTerpilih?->nama ?? 'Semua Produk' }}
                    @endif
                </h2>
                <span class="text-xs text-slate-500">{{ $produk->total() }} produk</span>
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
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-4">
                    @foreach ($produk as $item)
                        <x-storefront.product-card :produk="$item" />
                    @endforeach
                </div>

                <div>
                    {{ $produk->links() }}
                </div>
            @endif
        </div>

        {{-- KERANJANG & REKOMENDASI --}}
        <aside id="keranjang" class="min-w-0 scroll-mt-28">
            <div class="space-y-4 lg:sticky lg:top-20">
                <div class="kartu p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <x-icon.cart class="h-5 w-5" /> Keranjang
                            @if ($jumlahItem)
                                <span class="badge bg-brand-50 text-brand-700">{{ $jumlahItem }}</span>
                            @endif
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
                                    <img src="{{ $item['produk']->gambar_url }}" class="h-10 w-10 shrink-0 rounded-md object-contain" alt="">
                                    <div class="min-w-0 flex-1">
                                        <p class="line-clamp-2 text-sm font-medium leading-tight text-slate-900">{{ $item['produk']->nama }}</p>
                                        <p class="text-xs text-slate-500">{{ $item['produk']->harga_format }}</p>
                                    </div>
                                    {{-- Tombol - / + mengirim jumlah baru lewat atribut value; jumlah 0 berarti item dihapus. --}}
                                    <form method="POST" action="{{ route('keranjang.ubah', $item['produk']) }}" class="flex shrink-0 items-center gap-1">
                                        @csrf @method('PATCH')
                                        <button type="submit" name="jumlah" value="{{ $item['jumlah'] - 1 }}" class="rounded border border-slate-300 p-1 text-slate-600 hover:bg-slate-50" title="Kurangi">
                                            <x-icon.minus class="h-3.5 w-3.5" />
                                        </button>
                                        <span class="w-6 text-center text-sm font-medium text-slate-900">{{ $item['jumlah'] }}</span>
                                        <button type="submit" name="jumlah" value="{{ $item['jumlah'] + 1 }}" @disabled($item['jumlah'] >= $item['produk']->stok)
                                            class="rounded border border-slate-300 p-1 text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" title="Tambah">
                                            <x-icon.plus class="h-3.5 w-3.5" />
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('keranjang.hapus', $item['produk']) }}" class="shrink-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-600" title="Hapus">
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
                            <x-primary-button type="submit" class="w-full justify-center">Checkout</x-primary-button>
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

    {{-- Bar keranjang melayang (HP): total selalu terlihat, ketuk untuk ke keranjang. --}}
    @if ($jumlahItem)
        <div class="h-16 lg:hidden"></div>
        <a href="#keranjang" class="fixed inset-x-3 bottom-3 z-30 flex items-center justify-between rounded-xl bg-slate-900 px-4 py-3 text-white shadow-lg lg:hidden">
            <span class="flex items-center gap-2 text-sm">
                <x-icon.cart class="h-5 w-5" /> {{ $jumlahItem }} item
            </span>
            <span class="text-sm font-semibold">Rp {{ number_format($totalKeranjang, 0, ',', '.') }} &rarr;</span>
        </a>
    @endif
</x-storefront-layout>
