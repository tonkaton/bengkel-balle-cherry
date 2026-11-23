<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Model LogRekomendasi - "buku catatan" setiap rekomendasi yang pernah
 * dihasilkan RekomendasiService. Berguna buat admin yang penasaran kenapa
 * item A merekomendasikan item B, dan sejak kapan.
 *
 * Item asal maupun item yang direkomendasikan bisa sparepart atau servis,
 * jadi keduanya disimpan sebagai pasangan jenis + id (relasi polimorfik
 * "asal" dan "rekomendasi"; jenisnya "produk" atau "servis", lihat
 * AppServiceProvider).
 */
class LogRekomendasi extends Model
{
    use HasFactory;

    protected $table = 'log_rekomendasi';

    protected $fillable = [
        'asal_type',
        'asal_id',
        'rekomendasi_type',
        'rekomendasi_id',
        'skor',
        'metode',
        'tanggal',
    ];

    protected $casts = [
        'skor' => 'float',
        'tanggal' => 'datetime',
    ];

    public function asal(): MorphTo
    {
        return $this->morphTo();
    }

    public function rekomendasi(): MorphTo
    {
        return $this->morphTo();
    }
}
