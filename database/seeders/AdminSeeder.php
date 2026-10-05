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
     * PENTING: ganti password ini lewat menu "Ubah Password" begitu
     * aplikasi benar-benar dipakai di luar lingkungan uji coba/skripsi.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@ballechery.test'],
            [
                'name' => 'Admin Balle Chery',
                'password' => 'admin123',
            ]
        );
    }
}
