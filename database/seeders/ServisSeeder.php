<?php

namespace Database\Seeders;

use App\Models\Servis;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ServisSeeder extends Seeder
{
    /**
     * Dua paket jasa servis bengkel motor. Harga = jasa saja, belum termasuk
     * sparepart (riset harga pasaran bengkel umum: servis ringan Rp 50-150
     * ribu, servis besar matic Rp 210-400 ribu). Data yang sudah ada tidak
     * ditimpa, dan gambar contoh hanya disalin kalau servis belum punya gambar.
     */
    public function run(): void
    {
        $servis = [
            ['nama' => 'Servis Ringan', 'harga' => 100000, 'estimasi_menit' => 60, 'gambar' => 'servis-ringan.svg',
                'deskripsi' => 'Penggantian oli, pembersihan filter udara dan CVT, serta pengecekan rem, busi, dan lampu. Harga jasa saja, belum termasuk sparepart.'],
            ['nama' => 'Servis Besar', 'harga' => 250000, 'estimasi_menit' => 180, 'gambar' => 'servis-besar.svg',
                'deskripsi' => 'Pemeriksaan mesin menyeluruh, termasuk pembersihan ruang bakar dan skir klep. Harga jasa saja, belum termasuk sparepart.'],
        ];

        foreach ($servis as $item) {
            $model = Servis::firstOrCreate(['nama' => $item['nama']], collect($item)->except('gambar')->all());

            if (! $model->gambar || ! Storage::disk('public')->exists($model->gambar)) {
                $path = 'servis/'.$item['gambar'];
                Storage::disk('public')->put($path, file_get_contents(database_path('seeders/gambar/'.$item['gambar'])));
                $model->update(['gambar' => $path]);
            }
        }
    }
}
