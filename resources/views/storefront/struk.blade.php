<x-storefront-layout title="Struk Pembelian">
    <div class="mx-auto max-w-md">
        <div class="kartu overflow-hidden">
            <div class="border-b border-dashed border-slate-300 p-6 text-center">
                <p class="font-bold uppercase tracking-wide text-slate-900">Bengkel Balle Chery</p>
                <p class="mt-1 text-xs text-slate-500">{{ $transaksi->tanggal->format('d/m/Y H:i:s') }}</p>
                <p class="text-xs text-slate-500">No: {{ $transaksi->kode_transaksi }}</p>
            </div>

            <div class="space-y-2 p-6">
                @foreach ($transaksi->detailTransaksi as $item)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-700">{{ $item->produk->nama ?? 'Produk telah dihapus' }} &times;{{ $item->jumlah }}</span>
                        <span class="font-medium text-slate-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 p-6">
                <span class="font-semibold text-slate-900">Total</span>
                <span class="text-lg font-bold text-slate-900">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="mt-4 space-y-3">
            @if ($transaksi->ratingKomen)
                <div class="kartu p-4 text-center">
                    <p class="text-sm text-slate-600">Terima kasih sudah memberi rating!</p>
                    <div class="mt-1 flex justify-center gap-0.5">
                        @for ($i = 1; $i <= 5; $i++)
                            <x-icon.star :solid="$i <= $transaksi->ratingKomen->rating" class="h-5 w-5 {{ $i <= $transaksi->ratingKomen->rating ? 'text-amber-400' : 'text-slate-300' }}" />
                        @endfor
                    </div>
                </div>
            @else
                <a href="{{ route('rating.create', $transaksi->rating_token) }}" class="flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <x-icon.star class="h-5 w-5" /> Beri Rating &amp; Komentar
                </a>
            @endif

            <button onclick="window.print()" class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 print:hidden">
                Cetak Struk
            </button>

            <a href="{{ route('home') }}" class="block text-center text-sm text-slate-500 hover:text-slate-700 print:hidden">
                &larr; Kembali Belanja
            </a>
        </div>
    </div>
</x-storefront-layout>
