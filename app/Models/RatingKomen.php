<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model RatingKomen - ulasan bintang 1-5 beserta komentar bebas yang
 * pelanggan isi setelah checkout. Data di tabel inilah yang jadi bahan
 * utama perhitungan rekomendasi collaborative filtering di RekomendasiService.
 */
class RatingKomen extends Model
{
    use HasFactory;

    protected $table = 'rating_komen';

    protected $fillable = [
        'transaksi_id',
        'rating',
        'komen',
        'tanggal',
    ];

    protected $casts = [
        'rating' => 'integer',
        'tanggal' => 'datetime',
    ];

    /** Transaksi yang dinilai lewat rating ini. */
    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }
}
