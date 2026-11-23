<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Model Servis - jasa bengkel (Servis Ringan, Servis Besar, ...).
 *
 * Sengaja dipisah dari Produk: servis tidak punya stok maupun barcode dan
 * tidak dibeli lewat keranjang, melainkan dipesan lewat booking. Setelah
 * booking selesai dikerjakan, servis tercatat di detail_transaksi sehingga
 * ikut dihitung oleh RekomendasiService (KNN) bersama sparepart.
 */
class Servis extends Model
{
    use HasFactory;

    protected $table = 'servis';

    protected $fillable = [
        'nama',
        'deskripsi',
        'harga',
        'estimasi_menit',
        'gambar',
    ];

    protected $casts = [
        'harga' => 'integer',
        'estimasi_menit' => 'integer',
    ];

    public function booking(): HasMany
    {
        return $this->hasMany(BookingServis::class);
    }

    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class);
    }

    /** Sama seperti Produk::gambar_url: foto servis, atau placeholder kalau belum ada. */
    public function getGambarUrlAttribute(): string
    {
        if ($this->gambar && Storage::disk('public')->exists($this->gambar)) {
            return Storage::url($this->gambar);
        }

        return asset('images/produk-default.svg');
    }

    public function getHargaFormatAttribute(): string
    {
        return 'Rp '.number_format((float) $this->harga, 0, ',', '.');
    }

    /** Estimasi lama pengerjaan yang enak dibaca, contoh: "1 jam", "3 jam", "45 menit". */
    public function getEstimasiFormatAttribute(): string
    {
        return $this->estimasi_menit % 60 === 0
            ? intdiv($this->estimasi_menit, 60).' jam'
            : $this->estimasi_menit.' menit';
    }
}
