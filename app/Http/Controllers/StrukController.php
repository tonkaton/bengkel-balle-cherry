<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\View\View;

/**
 * StrukController - halaman struk/nota belanja yang bisa dicetak pelanggan.
 *
 * Perbaikan dibanding versi lama (Laporan Audit Teknis temuan F03): dulu
 * struk.php mengambil parameter "id" langsung dari URL dan menempelkannya
 * mentah-mentah ke query SQL, jadi bisa disisipi SQL Injection lewat
 * struk.php?id=... Di sini, Laravel route model binding yang menangani
 * pencarian transaksinya (lihat routes/web.php) - otomatis pakai
 * prepared statement dan otomatis balas halaman 404 kalau transaksinya
 * tidak ada, tanpa satu baris kode query manual pun perlu ditulis di sini.
 */
class StrukController extends Controller
{
    /** Tampilkan struk beserta daftar item dan rating (kalau sudah ada). */
    public function show(Transaksi $transaksi): View
    {
        $transaksi->load(['detailTransaksi.produk', 'ratingKomen']);

        return view('storefront.struk', ['transaksi' => $transaksi]);
    }
}
