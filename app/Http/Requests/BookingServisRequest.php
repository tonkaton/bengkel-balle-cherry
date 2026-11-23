<?php

namespace App\Http\Requests;

use App\Models\BookingServis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi form booking servis dari pelanggan (tanpa login). */
class BookingServisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'no_hp' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'kendaraan' => ['required', 'string', 'max:100'],
            'plat_nomor' => ['required', 'string', 'max:15'],
            // Booking paling cepat hari ini dan paling lambat 30 hari ke depan.
            'tanggal' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(30)->toDateString()],
            'jam' => ['required', Rule::in(BookingServis::JAM_LAYANAN), function (string $atribut, mixed $jam, \Closure $gagal) {
                // Untuk booking hari ini, jam yang sudah lewat tidak boleh dipilih.
                if ($this->input('tanggal') === now()->toDateString() && is_string($jam) && $jam <= now()->format('H:i')) {
                    $gagal('Jam tersebut sudah lewat untuk hari ini. Pilih jam lain atau tanggal berikutnya.');
                }
            }],
            'keluhan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_pelanggan' => 'nama',
            'no_hp' => 'nomor HP',
            'plat_nomor' => 'plat nomor',
        ];
    }

    public function messages(): array
    {
        return [
            'no_hp.regex' => 'Nomor HP harus nomor seluler Indonesia, contoh 081234567890.',
        ];
    }
}
