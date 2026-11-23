<x-admin-layout title="Booking Servis">
    @php($label = ['' => 'Semua', 'menunggu' => 'Menunggu', 'dikonfirmasi' => 'Dikonfirmasi', 'selesai' => 'Selesai', 'batal' => 'Batal'])
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($label as $kunci => $teks)
            <a href="{{ route('admin.booking.index', $kunci === '' ? [] : ['status' => $kunci]) }}"
                class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $status === $kunci ? 'bg-brand-600 text-white' : 'border border-slate-300 text-slate-600 hover:bg-slate-50' }}">
                {{ $teks }}
                @if ($kunci !== '') <span class="ml-1 text-xs opacity-75">{{ $jumlahPerStatus[$kunci] ?? 0 }}</span> @endif
            </a>
        @endforeach
    </div>

    <div class="kartu overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Jadwal</th>
                    <th class="px-4 py-3">Pelanggan</th>
                    <th class="px-4 py-3">Motor</th>
                    <th class="px-4 py-3">Servis</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($booking as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $item->kode_booking }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->tanggal->format('d/m/Y') }} {{ substr($item->jam, 0, 5) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->nama_pelanggan }}<br><span class="text-xs text-slate-400">{{ $item->no_hp }}</span></td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->kendaraan }}<br><span class="text-xs text-slate-400">{{ $item->plat_nomor }}</span></td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->servis->nama }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $item->status_warna }}">{{ $item->status_label }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.booking.show', $item) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada booking.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $booking->links() }}</div>
</x-admin-layout>
