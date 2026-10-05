<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RatingKomen;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin\RatingController - admin memantau & (kalau perlu) menghapus rating
 * dan komentar pelanggan, misalnya karena isinya spam atau tidak pantas.
 */
class RatingController extends Controller
{
    /** Daftar rating & komentar terbaru, lengkap dengan produk yang dibeli pada transaksi terkait. */
    public function index(): View
    {
        $rating = RatingKomen::with('transaksi.detailTransaksi.produk')
            ->latest('tanggal')
            ->paginate(15);

        return view('admin.rating.index', ['rating' => $rating]);
    }

    /** Hapus satu rating/komentar (moderasi). */
    public function destroy(RatingKomen $rating): RedirectResponse
    {
        $rating->delete();

        return back()->with('sukses', 'Rating & komentar berhasil dihapus.');
    }
}
