<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AnalisisStokService - mengelompokkan produk berdasarkan seberapa laris
 * penjualannya, lalu menyarankan jumlah stok yang masuk akal.
 *
 * PENTING: ini BUKAN algoritma machine learning apa pun, apalagi K-Nearest
 * Neighbor. Ini murni aturan ambang batas tetap (fixed threshold) - kalau
 * total terjual >= angka tertentu, masuk kategori tertentu. Versi PHP lama
 * menamai fitur ini "Analisis Stok dengan KNN" padahal isinya persis seperti
 * ini juga (lihat Laporan Audit Teknis temuan F08); bedanya di sini namanya
 * jujur sejak awal supaya tidak ada yang salah paham lagi soal algoritma
 * apa yang sebenarnya jalan.
 *
 * Aturan ambang batas ini sederhana tapi valid sebagai heuristik operasional
 * - banyak sistem inventori di dunia nyata memang memakai aturan seperti
 * ini, cuma jangan disebut "AI" atau "KNN" kalau memang bukan.
 */
class AnalisisStokService
{
    /**
     * Ambang batas total unit terjual untuk tiap kategori popularitas.
     * Angka-angka ini sama seperti versi PHP lama supaya hasilnya bisa
     * dibandingkan langsung dengan yang tercatat di laporan skripsi.
     */
    private const AMBANG_SANGAT_LARIS = 50;

    private const AMBANG_LARIS = 20;

    private const AMBANG_CUKUP_LARIS = 10;

    private const AMBANG_NORMAL = 5;

    /**
     * Hitung status popularitas + rekomendasi stok untuk semua produk,
     * diurutkan dari yang paling laris.
     *
     * @return Collection<int, object>
     */
    public function analisis(): Collection
    {
        return DB::table('produk')
            ->leftJoin('detail_transaksi', 'produk.id', '=', 'detail_transaksi.produk_id')
            ->select('produk.id', 'produk.nama', 'produk.barcode', 'produk.harga', 'produk.stok')
            ->selectRaw('COALESCE(SUM(detail_transaksi.jumlah), 0) as total_terjual')
            ->selectRaw('COUNT(DISTINCT detail_transaksi.transaksi_id) as jumlah_transaksi')
            ->groupBy('produk.id', 'produk.nama', 'produk.barcode', 'produk.harga', 'produk.stok')
            ->orderByDesc('total_terjual')
            ->get()
            ->map(function (object $baris) {
                $baris->status = $this->tentukanStatus((int) $baris->total_terjual);
                $baris->stok_rekomendasi = $this->hitungStokRekomendasi((int) $baris->total_terjual, (int) $baris->stok);

                return $baris;
            });
    }

    /**
     * Terjemahkan angka total terjual menjadi label status yang gampang
     * dibaca admin di tabel.
     */
    private function tentukanStatus(int $totalTerjual): string
    {
        return match (true) {
            $totalTerjual >= self::AMBANG_SANGAT_LARIS => 'Sangat Laris',
            $totalTerjual >= self::AMBANG_LARIS => 'Laris',
            $totalTerjual >= self::AMBANG_CUKUP_LARIS => 'Cukup Laris',
            $totalTerjual >= self::AMBANG_NORMAL => 'Normal',
            default => 'Sepi',
        };
    }

    /**
     * Sarankan jumlah stok baru berdasarkan status popularitasnya. Produk
     * yang sangat laris disarankan ditambah banyak, produk yang sepi
     * disarankan dikurangi supaya modal tidak menumpuk di barang yang
     * jarang laku.
     */
    private function hitungStokRekomendasi(int $totalTerjual, int $stokSaatIni): int
    {
        $pengali = match (true) {
            $totalTerjual >= self::AMBANG_SANGAT_LARIS => 3,
            $totalTerjual >= self::AMBANG_LARIS => 2,
            $totalTerjual >= self::AMBANG_CUKUP_LARIS => 1.5,
            $totalTerjual >= self::AMBANG_NORMAL => 1.2,
            default => 0.5,
        };

        return max((int) round($stokSaatIni * $pengali), 1);
    }
}
