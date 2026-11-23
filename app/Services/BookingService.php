<?php

namespace App\Services;

use App\Models\BookingServis;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

/**
 * BookingService - mengubah booking servis yang sudah dikerjakan menjadi
 * transaksi: satu baris jasa servis + sparepart yang dipakai mekanik.
 *
 * Aturannya sama dengan checkout (CheckoutController): harga diambil dari
 * database, stok sparepart dicek dan dikurangi di dalam satu transaksi
 * database dengan row lock, dan kalau satu sparepart saja stoknya kurang,
 * semuanya dibatalkan (rollback). Transaksi yang terbentuk punya struk dan
 * token rating sendiri, sehingga servis ikut masuk ke data KNN begitu
 * pelanggan memberi rating.
 */
class BookingService
{
    /**
     * @param  array<int, int>  $sparepart  [produk_id => jumlah]; jumlah 0 diabaikan
     *
     * @throws \RuntimeException kalau booking sudah tidak aktif atau stok sparepart kurang
     */
    public function selesaikan(BookingServis $booking, array $sparepart = []): Transaksi
    {
        return DB::transaction(function () use ($booking, $sparepart) {
            // Kunci baris booking dulu lalu cek statusnya di dalam transaksi: klik ganda
            // pada tombol Selesaikan tidak boleh menghasilkan dua transaksi.
            $booking = BookingServis::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if (! $booking->masih_aktif) {
                throw new \RuntimeException("Booking {$booking->kode_booking} sudah {$booking->status_label}.");
            }

            $transaksi = Transaksi::create(['total_harga' => 0]);

            $servis = $booking->servis;
            $transaksi->detailTransaksi()->create([
                'servis_id' => $servis->id,
                'jumlah' => 1,
                'harga_saat_beli' => $servis->harga,
            ]);
            $totalHarga = $servis->harga;

            foreach (array_filter($sparepart, fn ($jumlah) => $jumlah > 0) as $produkId => $jumlah) {
                $produk = Produk::whereKey($produkId)->lockForUpdate()->first();

                if (! $produk) {
                    throw new \RuntimeException('Sparepart yang dipilih tidak ditemukan.');
                }
                if ($produk->stok < $jumlah) {
                    throw new \RuntimeException("Stok {$produk->nama} tidak mencukupi (tersisa {$produk->stok}).");
                }

                $transaksi->detailTransaksi()->create([
                    'produk_id' => $produk->id,
                    'jumlah' => $jumlah,
                    'harga_saat_beli' => $produk->harga,
                ]);

                $produk->decrement('stok', $jumlah);
                $totalHarga += $produk->harga * $jumlah;
            }

            $transaksi->update(['total_harga' => $totalHarga]);
            $booking->update(['status' => BookingServis::SELESAI, 'transaksi_id' => $transaksi->id]);

            return $transaksi;
        });
    }
}
