<?php

namespace App\Http\Controllers;

use App\Http\Requests\RatingRequest;
use App\Models\Transaksi;
use App\Services\RekomendasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * RatingController - form bintang 1-5 + komentar yang diisi pelanggan
 * setelah checkout.
 *
 * Perbaikan dibanding versi lama (Laporan Audit Teknis temuan F11): dulu
 * siapa saja bisa mengirim rating atas nama transaksi ORANG LAIN, karena
 * yang dicek cuma "apakah transaksi_id ini sudah pernah dirating", tanpa
 * memverifikasi bahwa pengirimnya memang pembeli asli transaksi itu.
 * transaksi_id juga cuma angka urut (1, 2, 3, ...) yang gampang ditebak.
 *
 * Di sini, kedua route rating (lihat routes/web.php) mengikat transaksinya
 * lewat kolom rating_token - string acak 48 karakter yang cuma diketahui
 * oleh pembeli yang baru saja checkout (didapat dari redirect setelah
 * checkout). Tanpa token yang benar, Laravel bahkan tidak akan pernah
 * berhasil menemukan transaksinya (otomatis 404), jadi controller ini
 * tidak perlu menulis pengecekan kepemilikan secara manual sama sekali.
 */
class RatingController extends Controller
{
    /** Tampilkan form bintang + komentar, kecuali transaksi ini sudah pernah dirating. */
    public function create(Transaksi $transaksi): View|RedirectResponse
    {
        if ($transaksi->ratingKomen()->exists()) {
            return redirect()->route('struk.show', $transaksi)
                ->with('sukses', 'Transaksi ini sudah pernah diberi rating. Terima kasih!');
        }

        return view('storefront.rating', ['transaksi' => $transaksi]);
    }

    /** Simpan rating, lalu langsung hitung ulang rekomendasi karena ada data rating baru. */
    public function store(RatingRequest $request, Transaksi $transaksi, RekomendasiService $rekomendasi): RedirectResponse
    {
        if ($transaksi->ratingKomen()->exists()) {
            return redirect()->route('struk.show', $transaksi)
                ->with('sukses', 'Transaksi ini sudah pernah diberi rating. Terima kasih!');
        }

        $transaksi->ratingKomen()->create($request->validated());

        // Data rating baru saja bertambah, jadi wajar kalau katalog
        // rekomendasi langsung diperbarui - dijalankan sinkron di sini
        // karena datanya masih kecil; kalau nanti jumlah produk sudah
        // besar, baris ini tinggal diganti jadi antrean (queue) tanpa
        // mengubah RekomendasiService sama sekali.
        $rekomendasi->perbaruiSemuaLog();

        return redirect()->route('struk.show', $transaksi)
            ->with('sukses', 'Terima kasih atas rating dan komentarnya!');
    }
}
