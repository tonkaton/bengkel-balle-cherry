<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogRekomendasi;
use App\Models\Produk;
use App\Models\RatingKomen;
use App\Models\Transaksi;
use Illuminate\View\View;

/**
 * DashboardController - halaman pertama yang dilihat admin setelah login.
 *
 * Catatan penting soal kejujuran data (Laporan Audit Teknis temuan F06):
 * versi PHP lama menampilkan teks "Akurasi: 85-95%" di beberapa tempat
 * padahal angka itu ditulis tetap di HTML dan tidak pernah benar-benar
 * dihitung dari apa pun. Di dashboard ini SENGAJA tidak ada satu pun angka
 * "akurasi" yang dipajang, karena sistem ini belum pernah menjalani
 * evaluasi precision/recall yang layak (butuh data riil dalam jumlah besar
 * - lihat BAB II laporan skripsi). Semua angka yang ditampilkan di sini
 * adalah hasil COUNT/SUM asli dari database, bukan klaim yang dikarang.
 */
class DashboardController extends Controller
{
    /** Kumpulkan statistik ringkas + stok menipis + rating terbaru untuk halaman pertama admin. */
    public function index(): View
    {
        return view('admin.dashboard', [
            'totalProduk' => Produk::count(),
            'totalTransaksi' => Transaksi::count(),
            'totalPendapatan' => Transaksi::sum('total_harga'),
            'rataRating' => round((float) RatingKomen::avg('rating'), 1),
            'jumlahRating' => RatingKomen::count(),
            'rekomendasiBulanIni' => LogRekomendasi::whereMonth('tanggal', now()->month)->count(),
            'produkStokMenipis' => Produk::where('stok', '<=', 5)->orderBy('stok')->take(5)->get(),
            'ratingTerbaru' => RatingKomen::with('transaksi.detailTransaksi.produk')
                ->latest('tanggal')
                ->take(5)
                ->get(),
        ]);
    }
}
