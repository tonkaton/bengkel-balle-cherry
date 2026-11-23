<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => 'Servis '.fake()->unique()->word(),
            'deskripsi' => fake()->sentence(),
            'harga' => fake()->numberBetween(50000, 300000),
            'estimasi_menit' => 60,
        ];
    }
}
