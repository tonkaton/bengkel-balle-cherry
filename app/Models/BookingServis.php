<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Model BookingServis - jadwal servis yang dipesan pelanggan lewat form
 * booking. Pelanggan tidak punya akun, jadi booking dikenali lewat
 * kode_booking acak (bukan ID berurutan) yang dipakai di halaman status.
 */
class BookingServis extends Model
{
    use HasFactory;

    protected $table = 'booking_servis';

    public const MENUNGGU = 'menunggu';

    public const DIKONFIRMASI = 'dikonfirmasi';

    public const SELESAI = 'selesai';

    public const BATAL = 'batal';

    /** Jam layanan bengkel yang bisa dipilih di form booking. */
    public const JAM_LAYANAN = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];

    protected $fillable = [
        'kode_booking',
        'servis_id',
        'nama_pelanggan',
        'no_hp',
        'kendaraan',
        'plat_nomor',
        'tanggal',
        'jam',
        'keluhan',
        'status',
        'transaksi_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function servis(): BelongsTo
    {
        return $this->belongsTo(Servis::class);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    /** Booking yang masih bisa diproses admin (belum selesai dan belum batal). */
    public function getMasihAktifAttribute(): bool
    {
        return in_array($this->status, [self::MENUNGGU, self::DIKONFIRMASI], true);
    }

    /** Label status untuk tampilan, contoh: "Menunggu konfirmasi". */
    public function getStatusLabelAttribute(): string
    {
        return [
            self::MENUNGGU => 'Menunggu konfirmasi',
            self::DIKONFIRMASI => 'Dikonfirmasi',
            self::SELESAI => 'Selesai',
            self::BATAL => 'Dibatalkan',
        ][$this->status] ?? $this->status;
    }

    /** Nomor HP tersamar untuk halaman publik, contoh 0812******90. */
    public function getNoHpSamarAttribute(): string
    {
        return strlen($this->no_hp) > 6 ? substr($this->no_hp, 0, 4).str_repeat('*', strlen($this->no_hp) - 6).substr($this->no_hp, -2) : $this->no_hp;
    }

    /** Kelas warna badge Tailwind sesuai status. */
    public function getStatusWarnaAttribute(): string
    {
        return [
            self::MENUNGGU => 'bg-amber-50 text-amber-700',
            self::DIKONFIRMASI => 'bg-brand-50 text-brand-700',
            self::SELESAI => 'bg-emerald-50 text-emerald-700',
            self::BATAL => 'bg-slate-100 text-slate-500',
        ][$this->status] ?? 'bg-slate-100 text-slate-600';
    }

    protected static function booted(): void
    {
        static::creating(function (BookingServis $booking) {
            // Ulangi kalau kode acak kebetulan sudah dipakai (kolom kode_booking unik).
            while (! $booking->kode_booking || static::where('kode_booking', $booking->kode_booking)->exists()) {
                $booking->kode_booking = 'BKG'.now()->format('Ymd').strtoupper(Str::random(5));
            }
            $booking->status ??= self::MENUNGGU;
        });
    }
}
