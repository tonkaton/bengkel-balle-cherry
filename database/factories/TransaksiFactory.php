<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiFactory extends Factory
{
    /**
     * kode_transaksi dan rating_token sengaja tidak diisi di sini - keduanya
     * otomatis dibuat oleh Transaksi::booted() saat model disimpan (lihat
     * app/Models/Transaksi.php), persis seperti perilaku aslinya di
     * aplikasi.
     */
    public function definition(): array
    {
        return [
            'total_harga' => fake()->numberBetween(50000, 1000000),
        ];
    }
}
