<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogRekomendasi;
use App\Services\RekomendasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin\RekomendasiController - riwayat rekomendasi yang pernah dihasilkan
 * sistem, dan tombol untuk memperbaruinya secara manual.
 *
 * Perbaikan dibanding versi lama (Laporan Audit Teknis temuan F10): dulu
 * pembaruan rekomendasi bisa dipicu siapa saja lewat cron_update.php yang
 * bisa diakses langsung dari browser tanpa login. Di sini, tombol
 * "Perbarui Sekarang" ada di belakang middleware "auth" seperti seluruh
 * halaman admin lain, DAN proses yang sama juga tersedia sebagai perintah
 * terjadwal otomatis (lihat app/Console/Commands/PerbaruiRekomendasi.php)
 * supaya tidak perlu diklik manual setiap hari.
 */
class RekomendasiController extends Controller
{
    /** Riwayat rekomendasi terbaru, lengkap dengan metode (knn / co_purchase) yang menghasilkannya. */
    public function index(): View
    {
        $log = LogRekomendasi::with(['produkAsal', 'produkRekomendasi'])
            ->latest('tanggal')
            ->paginate(20);

        return view('admin.rekomendasi.index', ['log' => $log]);
    }

    /** Tombol manual "Perbarui Sekarang" - memanggil service yang sama dengan jadwal otomatis. */
    public function perbarui(RekomendasiService $service): RedirectResponse
    {
        $jumlah = $service->perbaruiSemuaLog();

        return back()->with('sukses', "Pembaruan selesai. {$jumlah} rekomendasi baru dicatat.");
    }
}
