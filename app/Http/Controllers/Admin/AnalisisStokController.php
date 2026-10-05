<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Services\AnalisisStokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin\AnalisisStokController - halaman "produk mana yang paling laris,
 * dan berapa stok yang disarankan".
 *
 * Dulu halaman ini bernama "Analisis Stok dengan KNN" (lihat Laporan Audit
 * Teknis temuan F08) padahal isinya aturan ambang batas biasa, bukan
 * algoritma machine learning. Di versi ini namanya diluruskan menjadi
 * "Analisis Popularitas Produk" supaya sesuai dengan apa yang benar-benar
 * dihitung oleh AnalisisStokService.
 */
class AnalisisStokController extends Controller
{
    /** Tampilkan tabel popularitas + rekomendasi stok untuk semua produk. */
    public function index(AnalisisStokService $service): View
    {
        return view('admin.analisis-stok.index', ['analisis' => $service->analisis()]);
    }

    /** Terapkan angka stok rekomendasi (hasil AnalisisStokService) ke satu produk saat admin klik "Terapkan". */
    public function updateStok(Produk $produk, AnalisisStokService $service): RedirectResponse
    {
        $rekomendasi = $service->analisis()->firstWhere('id', $produk->id);

        if (! $rekomendasi) {
            return back()->with('galat', 'Data analisis untuk produk ini tidak ditemukan.');
        }

        $produk->update(['stok' => $rekomendasi->stok_rekomendasi]);

        return back()->with('sukses', "Stok {$produk->nama} diperbarui menjadi {$rekomendasi->stok_rekomendasi} unit.");
    }
}
