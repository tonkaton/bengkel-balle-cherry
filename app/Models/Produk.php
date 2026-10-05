<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Model Produk - jantungnya katalog sparepart.
 *
 * Beberapa hal yang sengaja ditaruh di sini dan bukan di controller:
 * - Accessor gambar_url, supaya SETIAP tempat yang menampilkan produk
 *   (storefront, keranjang, admin, rekomendasi) otomatis dapat placeholder
 *   yang sama saat produk belum punya foto. Ini memperbaiki temuan F15 di
 *   Laporan Audit Teknis, di mana versi lama mengandalkan file default.png
 *   yang ternyata tidak pernah ada di folder uploads.
 * - Accessor harga_format, supaya format "Rp 65.000" tidak ditulis ulang
 *   berkali-kali di banyak file Blade yang berbeda.
 */
class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';

    protected $fillable = [
        'barcode',
        'nama',
        'harga',
        'stok',
        'kategori_id',
        'gambar',
    ];

    protected $casts = [
        'harga' => 'integer',
        'stok' => 'integer',
    ];

    /** Kategori tempat produk ini dikelompokkan (boleh kosong/nullable). */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    /** Semua baris detail_transaksi yang pernah membeli produk ini - dipakai untuk hitung total terjual. */
    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class);
    }

    /**
     * URL gambar produk yang siap dipakai langsung di tag <img>.
     *
     * Kalau produk belum punya foto (kolom "gambar" kosong) atau filenya
     * ternyata sudah terhapus dari storage, fungsi ini otomatis balik ke
     * gambar placeholder bawaan aplikasi (public/images/produk-default.svg)
     * alih-alih menampilkan ikon "gambar rusak" seperti di versi PHP lama.
     */
    public function getGambarUrlAttribute(): string
    {
        if ($this->gambar && Storage::disk('public')->exists($this->gambar)) {
            return Storage::url($this->gambar);
        }

        return asset('images/produk-default.svg');
    }

    /**
     * Harga dalam format Rupiah yang enak dibaca, contoh: "Rp 65.000".
     */
    public function getHargaFormatAttribute(): string
    {
        return 'Rp '.number_format((float) $this->harga, 0, ',', '.');
    }

    /**
     * True kalau stok sudah habis - dipakai buat menonaktifkan tombol
     * "Tambah ke Keranjang" di storefront.
     */
    public function getStokHabisAttribute(): bool
    {
        return $this->stok <= 0;
    }
}
