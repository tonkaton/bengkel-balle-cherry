<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Transaksi;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * CheckoutController - mengubah isi keranjang jadi transaksi tersimpan.
 *
 * Ini bagian paling sensitif di seluruh aplikasi karena menyangkut uang dan
 * stok. Dua aturan keras yang dipegang teguh di sini:
 *
 * 1. Harga SELALU diambil dari tabel produk di database saat proses ini
 *    berjalan, tidak pernah dari input apa pun yang datang dari luar.
 * 2. Pengecekan-dan-pengurangan stok dibungkus dalam satu transaksi
 *    database dengan row lock (lockForUpdate), supaya kalau ada dua
 *    pelanggan checkout produk yang sama nyaris bersamaan, stok tidak akan
 *    pernah minus akibat race condition.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function store(): RedirectResponse
    {
        if ($this->cart->kosong()) {
            return back()->with('galat', 'Keranjang masih kosong.');
        }

        $isiKeranjang = $this->cart->isi();

        try {
            $transaksi = DB::transaction(function () use ($isiKeranjang) {
                $totalHarga = 0;

                // Baris transaksi dibuat dulu dengan total sementara 0,
                // nanti di-update setelah semua item selesai diproses -
                // supaya kita punya transaksi_id untuk dilampirkan ke
                // setiap detail_transaksi.
                $transaksi = Transaksi::create(['total_harga' => 0]);

                foreach ($isiKeranjang as $item) {
                    // lockForUpdate() mengunci baris produk ini sampai
                    // transaksi selesai, supaya tidak ada checkout lain yang
                    // membaca angka stok "basi" di saat yang bersamaan.
                    $produk = Produk::whereKey($item['produk']->id)->lockForUpdate()->firstOrFail();

                    if ($produk->stok < $item['jumlah']) {
                        throw new \RuntimeException("Stok {$produk->nama} tidak mencukupi (tersisa {$produk->stok}).");
                    }

                    $transaksi->detailTransaksi()->create([
                        'produk_id' => $produk->id,
                        'jumlah' => $item['jumlah'],
                        'harga_saat_beli' => $produk->harga, // <- harga dari DB, bukan dari sesi/klien
                    ]);

                    $produk->decrement('stok', $item['jumlah']);
                    $totalHarga += $produk->harga * $item['jumlah'];
                }

                $transaksi->update(['total_harga' => $totalHarga]);

                return $transaksi;
            });
        } catch (\RuntimeException $galat) {
            // Kalau salah satu produk ternyata stoknya kurang di detik
            // terakhir, SELURUH transaksi dibatalkan otomatis oleh
            // DB::transaction (rollback) - tidak ada data setengah jadi
            // yang tersimpan.
            return back()->with('galat', $galat->getMessage());
        }

        $this->cart->kosongkan();

        return redirect()
            ->route('struk.show', $transaksi)
            ->with('sukses', 'Checkout berhasil! Terima kasih sudah berbelanja.');
    }
}
