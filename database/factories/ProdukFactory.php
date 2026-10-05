<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdukFactory extends Factory
{
    public function definition(): array
    {
        return [
            'barcode' => fake()->unique()->ean13(),
            'nama' => fake()->words(3, true),
            'harga' => fake()->numberBetween(10000, 500000),
            'stok' => fake()->numberBetween(5, 50),
            'kategori_id' => Kategori::factory(),
        ];
    }
}
