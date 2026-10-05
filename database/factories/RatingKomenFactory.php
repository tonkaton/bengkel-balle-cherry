<?php

namespace Database\Factories;

use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingKomenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transaksi_id' => Transaksi::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'komen' => fake()->optional()->sentence(),
        ];
    }
}
