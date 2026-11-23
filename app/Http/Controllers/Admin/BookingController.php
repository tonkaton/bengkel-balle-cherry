<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingServis;
use App\Models\Produk;
use App\Services\BookingService;
use App\Services\RekomendasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin\BookingController - mengelola booking servis: melihat daftar,
 * mengonfirmasi, membatalkan, dan menyelesaikan booking. Menyelesaikan
 * booking membuat transaksi (servis + sparepart yang dipakai) lewat
 * BookingService, lalu admin diarahkan ke struk yang berisi link rating.
 */
class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $booking = BookingServis::with('servis')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByRaw("FIELD(status, 'menunggu', 'dikonfirmasi', 'selesai', 'batal')")
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->paginate(15)
            ->withQueryString();

        return view('admin.booking.index', [
            'booking' => $booking,
            'status' => $status,
            'jumlahPerStatus' => BookingServis::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /**
     * Detail booking + form penyelesaian. Sparepart yang direkomendasikan KNN
     * untuk servis ini ditaruh paling atas daftar, supaya mekanik/admin
     * langsung melihat sparepart yang biasanya dipakai bersama servis itu.
     */
    public function show(BookingServis $booking, RekomendasiService $rekomendasi): View
    {
        $booking->load(['servis', 'transaksi']);
        $disarankan = $rekomendasi->rekomendasikanUntukServis($booking->servis_id)
            ->where('jenis', 'produk')
            ->keyBy(fn ($r) => $r['item']->id);

        $produk = Produk::orderBy('nama')->get()
            ->sortByDesc(fn ($p) => $disarankan[$p->id]['skor'] ?? 0)
            ->values();

        return view('admin.booking.show', ['booking' => $booking, 'produk' => $produk, 'disarankan' => $disarankan]);
    }

    public function konfirmasi(BookingServis $booking): RedirectResponse
    {
        // Update bersyarat (atomik): kalau status sudah berubah di request lain, tidak ada baris yang terubah.
        $berubah = BookingServis::whereKey($booking->id)->where('status', BookingServis::MENUNGGU)
            ->update(['status' => BookingServis::DIKONFIRMASI]);
        if (! $berubah) {
            return back()->with('galat', 'Hanya booking berstatus menunggu yang bisa dikonfirmasi.');
        }

        return back()->with('sukses', "Booking {$booking->kode_booking} dikonfirmasi.");
    }

    public function batal(BookingServis $booking): RedirectResponse
    {
        $berubah = BookingServis::whereKey($booking->id)->whereIn('status', [BookingServis::MENUNGGU, BookingServis::DIKONFIRMASI])
            ->update(['status' => BookingServis::BATAL]);
        if (! $berubah) {
            return back()->with('galat', 'Booking ini sudah tidak bisa dibatalkan.');
        }

        return back()->with('sukses', "Booking {$booking->kode_booking} dibatalkan.");
    }

    /** Selesaikan booking: buat transaksi servis + sparepart, lalu tampilkan struknya. */
    public function selesaikan(Request $request, BookingServis $booking, BookingService $service): RedirectResponse
    {
        $data = $request->validate([
            'sparepart' => ['nullable', 'array'],
            'sparepart.*' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        try {
            $transaksi = $service->selesaikan($booking, array_map('intval', $data['sparepart'] ?? []));
        } catch (\RuntimeException $galat) {
            return back()->with('galat', $galat->getMessage());
        }

        return redirect()
            ->route('struk.show', $transaksi)
            ->with('sukses', "Booking {$booking->kode_booking} selesai. Berikan link rating di struk ini kepada pelanggan.");
    }
}
