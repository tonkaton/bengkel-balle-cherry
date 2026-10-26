<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    /**
     * Lima kategori dasar yang sama seperti versi PHP lama (database.sql),
     * supaya data lama tetap bisa dibandingkan langsung dengan yang baru.
     */
    public function run(): void
    {
        foreach (['Mesin', 'Kelistrikan', 'Kaki-kaki', 'Body', 'Pelumas', 'Jasa Servis'] as $nama) {
            Kategori::firstOrCreate(['nama' => $nama]);
        }
    }
}
