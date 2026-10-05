<?php

namespace Database\Factories;

use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetailTransaksiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transaksi_id' => Transaksi::factory(),
            'produk_id' => Produk::factory(),
            'jumlah' => fake()->numberBetween(1, 3),
            'harga_saat_beli' => fake()->numberBetween(10000, 200000),
        ];
    }
}
