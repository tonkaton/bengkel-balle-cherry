<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogRekomendasi;
use App\Models\Produk;
use App\Models\Transaksi;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin\ExportController - unduh laporan dalam format CSV.
 *
 * Catatan jujur soal penamaan: versi PHP lama (export_excel.php) menyajikan
 * berkas teks yang dipisah tab dan menyebutnya "Excel" (Content-Type
 * application/vnd.ms-excel padahal isinya bukan format .xls sungguhan).
 * Kebetulan Excel cukup toleran membuka berkas semacam itu, tapi
 * penamaannya tetap menyesatkan. Di sini berkasnya jujur disebut CSV -
 * format standar yang bisa dibuka Excel, Google Sheets, atau aplikasi
 * spreadsheet apa pun tanpa masalah.
 */
class ExportController extends Controller
{
    /** Unduh seluruh riwayat transaksi beserta rating/komentarnya sebagai CSV. */
    public function transaksi(): StreamedResponse
    {
        return $this->unduhCsv('laporan_transaksi.csv', ['Kode Transaksi', 'Tanggal', 'Total', 'Rating', 'Komentar'], function ($tulisBaris) {
            Transaksi::with('ratingKomen')->orderByDesc('tanggal')->chunk(200, function ($semua) use ($tulisBaris) {
                foreach ($semua as $transaksi) {
                    $tulisBaris([
                        $transaksi->kode_transaksi,
                        $transaksi->tanggal->format('Y-m-d H:i:s'),
                        $transaksi->total_harga,
                        $transaksi->ratingKomen->rating ?? '',
                        $transaksi->ratingKomen->komen ?? '',
                    ]);
                }
            });
        });
    }

    /** Unduh daftar produk beserta total unit terjual sepanjang waktu sebagai CSV. */
    public function produk(): StreamedResponse
    {
        return $this->unduhCsv('laporan_produk.csv', ['ID', 'Nama Produk', 'Kategori', 'Harga', 'Stok', 'Total Terjual'], function ($tulisBaris) {
            Produk::withSum('detailTransaksi as total_terjual', 'jumlah')
                ->with('kategori')
                ->orderBy('nama')
                ->chunk(200, function ($semua) use ($tulisBaris) {
                    foreach ($semua as $produk) {
                        $tulisBaris([
                            $produk->id,
                            $produk->nama,
                            $produk->kategori->nama ?? '-',
                            $produk->harga,
                            $produk->stok,
                            $produk->total_terjual ?? 0,
                        ]);
                    }
                });
        });
    }

    /** Unduh 500 entri log_rekomendasi terbaru (skor + metode) sebagai CSV. */
    public function rekomendasi(): StreamedResponse
    {
        return $this->unduhCsv('laporan_rekomendasi.csv', ['Produk Asal', 'Produk Rekomendasi', 'Skor (%)', 'Metode', 'Tanggal'], function ($tulisBaris) {
            LogRekomendasi::with(['produkAsal', 'produkRekomendasi'])
                ->latest('tanggal')
                ->limit(500)
                ->get()
                ->each(function ($log) use ($tulisBaris) {
                    $tulisBaris([
                        $log->produkAsal->nama ?? '-',
                        $log->produkRekomendasi->nama ?? '-',
                        $log->skor,
                        $log->metode,
                        $log->tanggal->format('Y-m-d H:i:s'),
                    ]);
                });
        });
    }

    /**
     * Bungkus logika penulisan CSV yang berulang (header, BOM biar Excel
     * baca UTF-8 dengan benar, dan streaming supaya laporan besar tidak
     * membebani memori server) jadi satu tempat.
     */
    private function unduhCsv(string $namaBerkas, array $kolom, callable $isi): StreamedResponse
    {
        return response()->streamDownload(function () use ($kolom, $isi) {
            // Byte Order Mark supaya Excel di Windows otomatis mengenali
            // berkas ini sebagai UTF-8, bukan malah menampilkan huruf aneh
            // untuk nama produk yang mengandung karakter non-ASCII.
            echo "\xEF\xBB\xBF";

            $tangan = fopen('php://output', 'w');
            fputcsv($tangan, $kolom);

            $isi(function (array $baris) use ($tangan) {
                fputcsv($tangan, $baris);
            });

            fclose($tangan);
        }, $namaBerkas, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
