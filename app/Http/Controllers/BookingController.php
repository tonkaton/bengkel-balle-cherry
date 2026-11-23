<?php

namespace App\Http\Controllers;

use App\Models\BookingServis;
use Illuminate\View\View;

/**
 * BookingController - halaman status booking untuk pelanggan. Booking dicari
 * lewat kode_booking acak (route model binding di routes/web.php), bukan ID
 * berurutan, supaya booking orang lain tidak bisa ditebak.
 */
class BookingController extends Controller
{
    public function show(BookingServis $booking): View
    {
        $booking->load(['servis', 'transaksi.ratingKomen']);

        return view('storefront.booking', ['booking' => $booking]);
    }
}
