<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Kategori.
 *
 * Mewakili satu baris di tabel "kategori" (Mesin, Kelistrikan, dst).
 * Modelnya sengaja dibuat minimal karena memang tugasnya cuma jadi
 * pengelompok produk - tidak ada logika bisnis apa pun di sini.
 */
class Kategori extends Model
{
    use HasFactory;

    /** @var string Nama tabel eksplisit, karena "kategori" tidak mengikuti aturan jamak bawaan Laravel. */
    protected $table = 'kategori';

    /** @var list<string> Kolom yang boleh diisi lewat mass-assignment (Kategori::create([...])). */
    protected $fillable = ['nama'];

    /**
     * Semua produk yang tergabung dalam kategori ini.
     *
     * Relasi ini dipakai di halaman admin untuk menampilkan berapa banyak
     * produk per kategori, dan juga di storefront untuk fitur filter.
     */
    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class);
    }
}
