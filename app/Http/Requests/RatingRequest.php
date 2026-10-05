<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form rating & komentar yang diisi pelanggan setelah checkout.
 *
 * Catatan keamanan: siapa yang BOLEH mengisi rating untuk transaksi mana
 * sama sekali TIDAK diputuskan di sini. Itu tanggung jawab route model
 * binding pada kolom rating_token di routes/web.php (lihat komentar di
 * sana) - kalau token di URL tidak cocok dengan transaksi manapun, request
 * ini bahkan tidak akan pernah sampai ke controller. Form Request ini
 * murni soal bentuk datanya: angka rating harus 1-5, komentar boleh kosong.
 */
class RatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'komen' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
