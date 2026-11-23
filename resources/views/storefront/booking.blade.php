<x-storefront-layout title="Status Booking">
    <div class="mx-auto max-w-md">
        <div class="kartu overflow-hidden">
            <div class="border-b border-dashed border-slate-300 p-6 text-center">
                <p class="text-xs uppercase tracking-wide text-slate-500">Kode Booking</p>
                <p class="mt-1 text-xl font-bold tracking-wider text-slate-900">{{ $booking->kode_booking }}</p>
                <span class="badge mt-2 {{ $booking->status_warna }}">{{ $booking->status_label }}</span>
            </div>

            <dl class="space-y-2 p-6 text-sm">
                @foreach ([
                    'Servis' => $booking->servis->nama.' ('.$booking->servis->harga_format.')',
                    'Jadwal' => $booking->tanggal->translatedFormat('l, d F Y').' pukul '.substr($booking->jam, 0, 5),
                    'Nama' => $booking->nama_pelanggan,
                    'Nomor HP' => $booking->no_hp_samar,
                    'Motor' => $booking->kendaraan.' ('.$booking->plat_nomor.')',
                    'Keluhan' => $booking->keluhan ?: '-',
                ] as $label => $nilai)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="text-right font-medium text-slate-900">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="mt-4 space-y-3">
            @if ($booking->status === \App\Models\BookingServis::SELESAI && $booking->transaksi)
                <a href="{{ route('struk.show', $booking->transaksi) }}" class="flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    Lihat Struk Servis
                </a>
            @elseif ($booking->masih_aktif)
                <p class="text-center text-sm text-slate-500">Simpan kode booking ini. Halaman ini bisa dibuka kembali untuk mengecek status.</p>
            @endif

            <a href="{{ route('servis.index') }}" class="block text-center text-sm text-slate-500 hover:text-slate-700">&larr; Kembali ke daftar servis</a>
        </div>
    </div>
</x-storefront-layout>
