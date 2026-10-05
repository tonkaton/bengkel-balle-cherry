<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form "Tambah Produk Baru" di panel admin.
 *
 * Perbaikan dibanding versi lama (Laporan Audit Teknis temuan F14): kolom
 * kategori_id sekarang WAJIB diisi lewat dropdown, tidak seperti dulu yang
 * form-nya sama sekali tidak punya pilihan kategori sehingga setiap produk
 * baru otomatis kehilangan kategorinya.
 */
class StoreProdukRequest extends FormRequest
{
    /**
     * authorize() cuma dipanggil setelah middleware "auth" di routes/web.php
     * meloloskan request, jadi di titik ini pasti sudah admin yang login.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:50', 'unique:produk,barcode'],
            'nama' => ['required', 'string', 'max:150'],
            'harga' => ['required', 'integer', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'kategori_id' => ['required', 'exists:kategori,id'],
            // Batas 2MB dan tipe file dibatasi ketat supaya folder uploads
            // tidak kebanjiran file besar atau format aneh-aneh.
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
