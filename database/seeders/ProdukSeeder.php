<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProdukSeeder extends Seeder
{
    /**
     * Lima produk contoh yang sama seperti versi PHP lama (database.sql),
     * dipertahankan supaya data uji coba di laporan skripsi/audit tetap
     * relevan untuk dibandingkan dengan aplikasi Laravel ini, ditambah dua
     * paket jasa servis.
     */
    public function run(): void
    {
        $kategori = Kategori::pluck('id', 'nama');

        $produk = [
            ['barcode' => '8991234567890', 'nama' => 'Kampas Rem Depan', 'harga' => 85000, 'stok' => 10, 'kategori' => 'Kaki-kaki', 'gambar' => 'kampas-rem.svg'],
            ['barcode' => '8991234567891', 'nama' => 'Oli Mesin 1L', 'harga' => 65000, 'stok' => 20, 'kategori' => 'Mesin', 'gambar' => 'oli-mesin.svg'],
            ['barcode' => '8991234567892', 'nama' => 'Busi Iridium', 'harga' => 120000, 'stok' => 15, 'kategori' => 'Kelistrikan', 'gambar' => 'busi-iridium.svg'],
            ['barcode' => '8991234567893', 'nama' => 'Filter Udara', 'harga' => 45000, 'stok' => 8, 'kategori' => 'Mesin', 'gambar' => 'filter-udara.svg'],
            ['barcode' => '8991234567894', 'nama' => 'Aki Kering', 'harga' => 350000, 'stok' => 5, 'kategori' => 'Kelistrikan', 'gambar' => 'aki-kering.svg'],
            // Jasa servis dicatat sebagai produk berkategori "Jasa Servis" supaya bisa
            // masuk keranjang, checkout, dan rekomendasi seperti sparepart. Jasa tidak
            // punya stok fisik, jadi stoknya diisi angka besar (999) sebagai kuota.
            // Harga = jasa saja, belum termasuk part (riset harga pasaran bengkel umum:
            // servis ringan Rp 50-150 ribu, servis besar matic Rp 210-400 ribu).
            ['barcode' => 'SRV-RINGAN', 'nama' => 'Servis Ringan', 'harga' => 100000, 'stok' => 999, 'kategori' => 'Jasa Servis', 'gambar' => 'servis-ringan.svg'],
            ['barcode' => 'SRV-BESAR', 'nama' => 'Servis Besar', 'harga' => 250000, 'stok' => 999, 'kategori' => 'Jasa Servis', 'gambar' => 'servis-besar.svg'],
        ];

        foreach ($produk as $item) {
            $model = Produk::firstOrCreate(
                ['barcode' => $item['barcode']],
                [
                    'nama' => $item['nama'],
                    'harga' => $item['harga'],
                    'stok' => $item['stok'],
                    'kategori_id' => $kategori[$item['kategori']] ?? null,
                ]
            );

            // Gambar contoh (database/seeders/gambar) disalin ke storage publik,
            // hanya untuk produk yang belum punya gambar atau berkasnya hilang -
            // foto yang sudah diunggah admin tidak ditimpa.
            if (! $model->gambar || ! Storage::disk('public')->exists($model->gambar)) {
                $path = 'produk/'.$item['gambar'];
                Storage::disk('public')->put($path, file_get_contents(database_path('seeders/gambar/'.$item['gambar'])));
                $model->update(['gambar' => $path]);
            }
        }
    }
}
