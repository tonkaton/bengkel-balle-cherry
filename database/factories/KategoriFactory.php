<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class KategoriFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->randomElement(['Mesin', 'Kelistrikan', 'Kaki-kaki', 'Body', 'Pelumas']),
        ];
    }
}
