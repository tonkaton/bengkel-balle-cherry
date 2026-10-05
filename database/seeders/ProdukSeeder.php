<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    /**
     * Lima produk contoh yang sama seperti versi PHP lama (database.sql),
     * dipertahankan supaya data uji coba di laporan skripsi/audit tetap
     * relevan untuk dibandingkan dengan aplikasi Laravel ini.
     */
    public function run(): void
    {
        $kategori = Kategori::pluck('id', 'nama');

        $produk = [
            ['barcode' => '8991234567890', 'nama' => 'Kampas Rem Depan', 'harga' => 85000, 'stok' => 10, 'kategori' => 'Kaki-kaki'],
            ['barcode' => '8991234567891', 'nama' => 'Oli Mesin 1L', 'harga' => 65000, 'stok' => 20, 'kategori' => 'Mesin'],
            ['barcode' => '8991234567892', 'nama' => 'Busi Iridium', 'harga' => 120000, 'stok' => 15, 'kategori' => 'Kelistrikan'],
            ['barcode' => '8991234567893', 'nama' => 'Filter Udara', 'harga' => 45000, 'stok' => 8, 'kategori' => 'Mesin'],
            ['barcode' => '8991234567894', 'nama' => 'Aki Kering', 'harga' => 350000, 'stok' => 5, 'kategori' => 'Kelistrikan'],
        ];

        foreach ($produk as $item) {
            Produk::firstOrCreate(
                ['barcode' => $item['barcode']],
                [
                    'nama' => $item['nama'],
                    'harga' => $item['harga'],
                    'stok' => $item['stok'],
                    'kategori_id' => $kategori[$item['kategori']] ?? null,
                ]
            );
        }
    }
}
