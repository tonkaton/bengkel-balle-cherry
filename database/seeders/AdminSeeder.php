<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Bikin satu akun admin default.
     *
     * Perbaikan dibanding versi lama (Laporan Audit Teknis temuan F04):
     * dulu password disimpan pakai MD5('admin123') tanpa salt - hash yang
     * bisa dipecahkan dalam hitungan detik pakai rainbow table yang
     * beredar bebas di internet. Di sini, cukup menulis password-nya apa
     * adanya ('admin123', dipertahankan supaya familiar buat yang terbiasa
     * dengan versi lama) - model User (lihat app/Models/User.php) sudah
     * otomatis meng-hash-nya pakai bcrypt lewat cast 'hashed' bawaan
     * Laravel, jadi tidak ada risiko lupa meng-hash secara manual.
     *
     * Email dan password admin bisa diatur lewat ADMIN_EMAIL dan
     * ADMIN_PASSWORD di .env; kalau tidak diisi, dipakai nilai default
     * di bawah ('admin123') yang hanya cocok untuk uji coba lokal.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@ballechery.test')],
            [
                'name' => 'Admin Balle Chery',
                'password' => env('ADMIN_PASSWORD', 'admin123'),
            ]
        );
    }
}
