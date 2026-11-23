<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model DetailTransaksi - satu baris = satu item di dalam satu transaksi
 * (kalau checkout beli 3 produk berbeda, berarti ada 3 baris di sini).
 * Item bisa berupa sparepart (produk_id) atau jasa servis (servis_id) dari
 * booking yang sudah selesai; salah satunya selalu kosong.
 */
class DetailTransaksi extends Model
{
    use HasFactory;

    protected $table = 'detail_transaksi';

    protected $fillable = [
        'transaksi_id',
        'produk_id',
        'servis_id',
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

    public function servis(): BelongsTo
    {
        return $this->belongsTo(Servis::class);
    }

    /** Nama item untuk struk dan laporan, baik sparepart maupun servis. */
    public function getNamaItemAttribute(): string
    {
        return $this->servis_id
            ? ($this->servis->nama ?? 'Servis telah dihapus')
            : ($this->produk->nama ?? 'Produk telah dihapus');
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
