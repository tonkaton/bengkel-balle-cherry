<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model LogRekomendasi - "buku catatan" setiap rekomendasi yang pernah
 * dihasilkan RekomendasiService. Berguna buat admin yang penasaran kenapa
 * produk A merekomendasikan produk B, dan sejak kapan.
 *
 * Setiap baris punya DUA relasi ke tabel produk yang sama (produk asal dan
 * produk yang direkomendasikan), makanya nama kolom foreign key-nya harus
 * ditulis eksplisit - Laravel tidak bisa menebak sendiri mana yang mana.
 */
class LogRekomendasi extends Model
{
    use HasFactory;

    protected $table = 'log_rekomendasi';

    protected $fillable = [
        'produk_asal_id',
        'produk_rekomendasi_id',
        'skor',
        'metode',
        'tanggal',
    ];

    protected $casts = [
        'skor' => 'float',
        'tanggal' => 'datetime',
    ];

    /** Produk yang sedang dilihat/dibeli pelanggan (pemicu rekomendasi). */
    public function produkAsal(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_asal_id');
    }

    /** Produk yang disarankan sistem sebagai pelengkap produk asal. */
    public function produkRekomendasi(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_rekomendasi_id');
    }
}
