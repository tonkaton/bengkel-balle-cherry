<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingServisRequest;
use App\Models\BookingServis;
use App\Models\Servis;
use App\Services\RekomendasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * ServisController - halaman jasa servis untuk pelanggan: daftar servis,
 * form booking, dan penyimpanan booking. Pelanggan tidak perlu akun; cukup
 * nama dan nomor HP, lalu dapat kode booking untuk mengecek statusnya.
 */
class ServisController extends Controller
{
    /** Daftar semua jasa servis beserta harga dan estimasi pengerjaannya. */
    public function index(): View
    {
        return view('storefront.servis.index', ['servis' => Servis::orderBy('harga')->get()]);
    }

    /**
     * Form booking untuk satu servis, ditemani rekomendasi KNN: sparepart
     * (atau servis lain) yang paling sering diambil bersama servis ini.
     */
    public function booking(Servis $servis, RekomendasiService $rekomendasi): View
    {
        return view('storefront.servis.booking', [
            'servis' => $servis,
            'rekomendasi' => $rekomendasi->rekomendasikanUntukServis($servis->id),
            'jamLayanan' => BookingServis::JAM_LAYANAN,
        ]);
    }

    /** Simpan booking baru, lalu arahkan ke halaman status booking. */
    public function simpanBooking(BookingServisRequest $request, Servis $servis): RedirectResponse
    {
        $booking = $servis->booking()->create($request->validated());

        return redirect()
            ->route('booking.show', $booking)
            ->with('sukses', 'Booking berhasil dibuat. Simpan kode booking Anda untuk mengecek status.');
    }
}
