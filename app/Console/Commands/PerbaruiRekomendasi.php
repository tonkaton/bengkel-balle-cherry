<?php

namespace App\Console\Commands;

use App\Services\RekomendasiService;
use Illuminate\Console\Command;

/**
 * Perintah: php artisan rekomendasi:perbarui
 *
 * Ini pengganti langsung dari cron_update.php dan update_rekomendasi.php
 * pada versi PHP lama. Perbedaan paling penting (lihat Laporan Audit
 * Teknis temuan F10): dulu kedua berkas itu adalah HALAMAN WEB yang bisa
 * diakses siapa saja lewat browser tanpa login sama sekali, karena
 * memang sengaja dibuat begitu supaya bisa "dipanggil oleh cron job" -
 * padahal cron job seharusnya tidak pernah lewat HTTP publik.
 *
 * Sekarang, proses ini hanya bisa dijalankan dari command line server
 * (php artisan ...) atau lewat scheduler bawaan Laravel (lihat
 * routes/console.php) - sama sekali tidak terpasang di routes/web.php,
 * jadi tidak mungkin dipicu dari browser oleh siapa pun.
 */
class PerbaruiRekomendasi extends Command
{
    protected $signature = 'rekomendasi:perbarui';

    protected $description = 'Hitung ulang rekomendasi produk berdasarkan data transaksi & rating terbaru.';

    public function handle(RekomendasiService $service): int
    {
        $this->info('Menghitung ulang rekomendasi produk...');

        $jumlah = $service->perbaruiSemuaLog();

        $this->info("Selesai. {$jumlah} entri rekomendasi baru dicatat ke log_rekomendasi.");

        return self::SUCCESS;
    }
}
