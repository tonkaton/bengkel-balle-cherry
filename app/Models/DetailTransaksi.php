<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model DetailTransaksi - satu baris = satu produk di dalam satu transaksi
 * (kalau checkout beli 3 produk berbeda, berarti ada 3 baris di sini).
 */
class DetailTransaksi extends Model
{
    use HasFactory;

    protected $table = 'detail_transaksi';

    protected $fillable = [
        'transaksi_id',
        'produk_id',
        'jumlah',
        'harga_saat_beli',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'harga_saat_beli' => 'integer',
    ];

    /** Transaksi induk baris ini. */
    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    /** Produk yang dibeli pada baris ini. */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    /**
     * Subtotal baris ini (harga saat beli dikali jumlah). Sengaja dihitung
     * dari harga_saat_beli yang dibekukan di waktu checkout, bukan dari
     * harga produk saat ini - supaya struk lama tetap akurat meskipun
     * harga produk sudah berubah belakangan.
     */
    public function getSubtotalAttribute(): int
    {
        return $this->jumlah * $this->harga_saat_beli;
    }
}
