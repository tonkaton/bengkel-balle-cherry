<x-admin-layout title="Booking {{ $booking->kode_booking }}">
    <a href="{{ route('admin.booking.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-slate-700">&larr; Semua booking</a>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[360px_1fr]">
        <div class="kartu h-fit p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">{{ $booking->kode_booking }}</h2>
                <span class="badge {{ $booking->status_warna }}">{{ $booking->status_label }}</span>
            </div>
            <dl class="space-y-2 text-sm">
                @foreach ([
                    'Servis' => $booking->servis->nama.' ('.$booking->servis->harga_format.')',
                    'Jadwal' => $booking->tanggal->format('d/m/Y').' '.substr($booking->jam, 0, 5),
                    'Pelanggan' => $booking->nama_pelanggan,
                    'Nomor HP' => $booking->no_hp,
                    'Motor' => $booking->kendaraan.' ('.$booking->plat_nomor.')',
                    'Keluhan' => $booking->keluhan ?: '-',
                ] as $l => $v)
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ $l }}</dt><dd class="text-right text-slate-900">{{ $v }}</dd></div>
                @endforeach
            </dl>

            @if ($booking->masih_aktif)
                <div class="mt-5 flex gap-2">
                    @if ($booking->status === \App\Models\BookingServis::MENUNGGU)
                        <form method="POST" action="{{ route('admin.booking.konfirmasi', $booking) }}">
                            @csrf @method('PATCH')
                            <x-primary-button type="submit">Konfirmasi</x-primary-button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.booking.batal', $booking) }}" onsubmit="return confirm('Batalkan booking {{ $booking->kode_booking }}?')">
                        @csrf @method('PATCH')
                        <button type="submit" class="rounded-lg border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Batalkan</button>
                    </form>
                </div>
            @elseif ($booking->transaksi)
                <a href="{{ route('struk.show', $booking->transaksi) }}" class="mt-5 inline-block text-sm font-medium text-brand-600 hover:underline">Lihat struk {{ $booking->transaksi->kode_transaksi }} &rarr;</a>
            @endif
        </div>

        @if ($booking->masih_aktif)
            <div class="kartu p-5">
                <h2 class="font-semibold text-slate-900">Selesaikan Servis</h2>
                <p class="mb-4 text-sm text-slate-500">
                    Isi jumlah sparepart yang dipakai (kosongkan jika tidak ada). Sistem membuat transaksi berisi jasa servis dan sparepart,
                    mengurangi stok, lalu menampilkan struk beserta link rating untuk pelanggan. Sparepart bertanda KNN adalah yang paling sering dipakai bersama servis ini.
                </p>
                <form method="POST" action="{{ route('admin.booking.selesaikan', $booking) }}" onsubmit="return confirm('Selesaikan booking dan buat transaksi?')">
                    @csrf
                    <div class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                        @foreach ($produk as $item)
                            <label class="flex items-center gap-3 px-3 py-2 text-sm">
                                <img src="{{ $item->gambar_url }}" alt="" class="h-9 w-9 rounded object-cover">
                                <span class="flex-1">
                                    <span class="font-medium text-slate-900">{{ $item->nama }}</span>
                                    <span class="block text-xs text-slate-500">{{ $item->harga_format }} &middot; stok {{ $item->stok }}</span>
                                </span>
                                @isset($disarankan[$item->id])
                                    <span class="badge bg-brand-50 text-brand-700">KNN {{ $disarankan[$item->id]['skor'] }}%</span>
                                @endisset
                                <input type="number" name="sparepart[{{ $item->id }}]" min="0" max="{{ min(99, $item->stok) }}" value="{{ old('sparepart.'.$item->id, 0) }}"
                                    class="w-20 rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" @disabled($item->stok < 1)>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('sparepart.*')" class="mt-1" />
                    <x-primary-button type="submit" class="mt-4">Selesaikan &amp; Buat Transaksi</x-primary-button>
                </form>
            </div>
        @endif
    </div>
</x-admin-layout>
