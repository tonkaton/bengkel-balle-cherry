<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi form tambah/ubah jasa servis di panel admin. */
class ServisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100', Rule::unique('servis', 'nama')->ignore($this->route('servis'))],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'harga' => ['required', 'integer', 'min:0'],
            'estimasi_menit' => ['required', 'integer', 'min:15', 'max:600'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return ['estimasi_menit' => 'estimasi pengerjaan'];
    }
}
