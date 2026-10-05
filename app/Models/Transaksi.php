<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Model Transaksi - satu baris di sini = satu kali checkout pelanggan.
 */
class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';

    protected $fillable = [
        'kode_transaksi',
        'total_harga',
        'rating_token',
        'tanggal',
    ];

    protected $casts = [
        'total_harga' => 'integer',
        'tanggal' => 'datetime',
    ];

    /**
     * Semua barang yang dibeli pada transaksi ini.
     */
    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class);
    }

    /**
     * Rating & komentar untuk transaksi ini, kalau sudah diisi pelanggan.
     * Pakai hasOne karena satu transaksi memang cuma boleh dinilai sekali
     * (dijaga juga oleh constraint UNIQUE di kolom transaksi_id pada
     * tabel rating_komen).
     */
    public function ratingKomen(): HasOne
    {
        return $this->hasOne(RatingKomen::class);
    }

    /**
     * Otomatis diisi setiap kali transaksi baru dibuat, supaya controller
     * tidak perlu (dan tidak boleh) mengarang sendiri kode transaksi atau
     * token rating - dua hal ini murni tanggung jawab model.
     *
     * - kode_transaksi: nomor struk yang tampil ke pelanggan, formatnya
     *   "TRX" + tanggal + kode acak singkat supaya tidak gampang ditebak
     *   urutannya (berbeda dari versi lama yang cuma pakai time()+rand()
     *   tiga digit).
     * - rating_token: kunci rahasia panjang yang WAJIB disertakan saat
     *   mengirim rating. Ini perbaikan untuk temuan F11 di Laporan Audit
     *   Teknis - dulu siapa saja bisa menebak ID transaksi lalu mengirim
     *   rating palsu atas nama transaksi orang lain.
     */
    protected static function booted(): void
    {
        static::creating(function (Transaksi $transaksi) {
            $transaksi->kode_transaksi ??= 'TRX'.now()->format('Ymd').strtoupper(Str::random(6));
            $transaksi->rating_token ??= Str::random(48);
        });
    }
}
