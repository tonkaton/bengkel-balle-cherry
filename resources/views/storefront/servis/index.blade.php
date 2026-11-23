<x-storefront-layout title="Servis & Booking">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Jasa Servis</h1>
        <p class="mt-1 text-sm text-slate-500">Pilih jenis servis, lalu isi form booking untuk menjadwalkan kedatangan. Harga adalah tarif jasa, belum termasuk sparepart yang diganti.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($servis as $item)
            <div class="kartu flex flex-col overflow-hidden">
                <div class="aspect-[16/9] w-full overflow-hidden bg-slate-100">
                    <img src="{{ $item->gambar_url }}" alt="{{ $item->nama }}" class="h-full w-full object-contain">
                </div>
                <div class="flex flex-1 flex-col gap-2 p-5">
                    <h2 class="font-semibold text-slate-900">{{ $item->nama }}</h2>
                    @if ($item->deskripsi)
                        <p class="text-sm text-slate-600">{{ $item->deskripsi }}</p>
                    @endif
                    <div class="mt-auto flex items-end justify-between pt-3">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">{{ $item->harga_format }}</p>
                            <p class="text-xs text-slate-500">Estimasi {{ $item->estimasi_format }}</p>
                        </div>
                        <a href="{{ route('servis.booking', $item) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                            <x-icon.calendar class="h-4 w-4" /> Booking
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">Belum ada jasa servis yang tersedia.</p>
        @endforelse
    </div>
</x-storefront-layout>
