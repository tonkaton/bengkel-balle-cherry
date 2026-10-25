<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan di bawah ini sengaja tidak boleh diacak: kategori harus ada
     * dulu sebelum produk (karena produk butuh kategori_id), produk harus ada
     * sebelum transaksi contoh, dan admin bisa kapan saja karena tidak
     * bergantung ke tabel lain.
     */
    public function run(): void
    {
        $this->call([
            KategoriSeeder::class,
            ProdukSeeder::class,
            TransaksiSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
