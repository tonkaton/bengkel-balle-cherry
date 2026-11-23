<?php

namespace Database\Factories;

use App\Models\Servis;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingServisFactory extends Factory
{
    /** kode_booking dan status dibuat otomatis oleh BookingServis::booted(). */
    public function definition(): array
    {
        return [
            'servis_id' => Servis::factory(),
            'nama_pelanggan' => fake()->name(),
            'no_hp' => '0812'.fake()->numerify('########'),
            'kendaraan' => 'Honda Vario 125',
            'plat_nomor' => 'B '.fake()->numerify('####').' ABC',
            'tanggal' => now()->addDay()->toDateString(),
            'jam' => '09:00',
        ];
    }
}
