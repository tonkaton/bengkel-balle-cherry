<x-admin-layout title="Rating & Komentar">
    <div class="space-y-3">
        @forelse ($rating as $item)
            <div class="kartu p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $item->transaksi->kode_transaksi ?? '-' }}</p>
                        <p class="text-xs text-slate-400">{{ $item->tanggal->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="flex gap-0.5">
                        @for ($i = 1; $i <= 5; $i++)
                            <x-icon.star :solid="$i <= $item->rating" class="h-5 w-5 {{ $i <= $item->rating ? 'text-amber-400' : 'text-slate-200' }}" />
                        @endfor
                    </div>
                </div>

                @if ($item->transaksi)
                    <p class="mt-2 text-xs text-slate-500">
                        Produk dibeli:
                        <span class="text-emerald-600">
                            {{ $item->transaksi->detailTransaksi->map(fn ($d) => ($d->produk->nama ?? '?').' ('.$d->jumlah.'x)')->implode(', ') }}
                        </span>
                    </p>
                @endif

                @if ($item->komen)
                    <p class="mt-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">&ldquo;{{ $item->komen }}&rdquo;</p>
                @endif

                <form method="POST" action="{{ route('admin.rating.destroy', $item) }}" onsubmit="return confirm('Hapus rating ini?')" class="mt-3">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs font-medium text-rose-600 hover:underline">Hapus</button>
                </form>
            </div>
        @empty
            <div class="kartu p-10 text-center text-slate-400">Belum ada rating &amp; komentar dari pelanggan.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $rating->links() }}</div>
</x-admin-layout>
