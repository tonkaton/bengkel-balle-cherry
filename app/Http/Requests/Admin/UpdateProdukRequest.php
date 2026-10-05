<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form ubah data produk. Mirip StoreProdukRequest, tapi aturan
 * unique untuk barcode harus mengabaikan baris produk itu sendiri -
 * kalau tidak, admin tidak akan pernah bisa menyimpan ulang produk tanpa
 * mengubah barcode-nya.
 */
class UpdateProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $produk = $this->route('produk');

        return [
            'barcode' => ['required', 'string', 'max:50', Rule::unique('produk', 'barcode')->ignore($produk->id)],
            'nama' => ['required', 'string', 'max:150'],
            'harga' => ['required', 'integer', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'kategori_id' => ['required', 'exists:kategori,id'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kategori_id' => 'kategori',
        ];
    }
}
