<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Jadwal otomatis
|--------------------------------------------------------------------------
|
| Rekomendasi produk dihitung ulang otomatis setiap tengah malam, supaya
| admin tidak perlu ingat untuk mengklik tombol "Perbarui Sekarang" secara
| manual setiap hari. Ini menggantikan peran cron_update.php di versi PHP
| lama, tapi dengan cara yang jauh lebih aman (lihat komentar lengkap di
| app/Console/Commands/PerbaruiRekomendasi.php).
|
| Supaya baris ini benar-benar jalan setiap hari, server produksi perlu
| menjadwalkan SATU baris cron bawaan Laravel:
|   * * * * * php /path/ke/project/artisan schedule:run >> /dev/null 2>&1
*/
Schedule::command('rekomendasi:perbarui')->dailyAt('00:30');
