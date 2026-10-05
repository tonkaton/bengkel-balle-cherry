<?php

namespace App\Services;

use App\Models\Produk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * CartService - keranjang belanja yang disimpan di session server, BUKAN
 * di localStorage browser seperti versi PHP lama.
 *
 * Alasan pentingnya bukan cuma soal gaya - ini perbaikan keamanan. Di versi
 * lama, browser mengirim harga produk secara langsung ke server saat
 * checkout ("harga": 350000 di body JSON), dan server MEMPERCAYAI angka itu
 * mentah-mentah tanpa mencocokkan ke tabel produk. Itu artinya siapa pun
 * yang paham DevTools bisa mengubah angka harga sebelum checkout dan
 * membayar semurah yang dia mau. Bug ini tidak sempat masuk ke Laporan
 * Audit Teknis sebelumnya, tapi tetap termasuk pelanggaran prinsip yang
 * sama seperti temuan-temuan lain di sana: jangan pernah percaya data dari
 * klien untuk sesuatu yang menyangkut uang.
 *
 * Di sini, session cuma menyimpan [produk_id => jumlah] - sesederhana
 * mungkin. Setiap kali keranjang ditampilkan atau di-checkout, harga dan
 * nama produk SELALU diambil ulang dari database, bukan dari apa yang
 * "diingat" oleh sesi sebelumnya.
 */
class CartService
{
    private const SESSION_KEY = 'keranjang';

    /**
     * Ambil isi keranjang lengkap dengan data produk terbaru (harga, stok,
     * nama semuanya fresh dari database), siap ditampilkan di Blade.
     *
     * @return Collection<int, array{produk: Produk, jumlah: int, subtotal: int}>
     */
    public function isi(): Collection
    {
        $mentah = Session::get(self::SESSION_KEY, []);

        if (empty($mentah)) {
            return collect();
        }

        $produkTersedia = Produk::whereIn('id', array_keys($mentah))->get()->keyBy('id');

        return collect($mentah)
            ->filter(fn (int $jumlah, int $produkId) => $produkTersedia->has($produkId))
            ->map(function (int $jumlah, int $produkId) use ($produkTersedia) {
                $produk = $produkTersedia[$produkId];

                return [
                    'produk' => $produk,
                    'jumlah' => $jumlah,
                    'subtotal' => $produk->harga * $jumlah,
                ];
            })
            ->values();
    }

    /**
     * Tambah satu produk ke keranjang. Kalau produk itu sudah ada, jumlahnya
     * ditambah, bukan ditumpuk sebagai baris baru.
     */
    public function tambah(int $produkId, int $jumlah = 1): void
    {
        $keranjang = Session::get(self::SESSION_KEY, []);
        $keranjang[$produkId] = ($keranjang[$produkId] ?? 0) + max(1, $jumlah);
        Session::put(self::SESSION_KEY, $keranjang);
    }

    /**
     * Ubah jumlah salah satu produk secara langsung (dipakai tombol +/- di
     * halaman keranjang). Kalau hasilnya nol atau kurang, produk itu
     * otomatis dikeluarkan dari keranjang.
     */
    public function ubahJumlah(int $produkId, int $jumlah): void
    {
        if ($jumlah <= 0) {
            $this->hapus($produkId);

            return;
        }

        $keranjang = Session::get(self::SESSION_KEY, []);
        $keranjang[$produkId] = $jumlah;
        Session::put(self::SESSION_KEY, $keranjang);
    }

    /** Keluarkan satu produk dari keranjang sepenuhnya (bukan sekadar set jumlah ke 0). */
    public function hapus(int $produkId): void
    {
        $keranjang = Session::get(self::SESSION_KEY, []);
        unset($keranjang[$produkId]);
        Session::put(self::SESSION_KEY, $keranjang);
    }

    /** Buang seluruh isi keranjang dari session. */
    public function kosongkan(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /** True kalau keranjang tidak berisi apa-apa - dipakai buat validasi sebelum checkout. */
    public function kosong(): bool
    {
        return empty(Session::get(self::SESSION_KEY, []));
    }

    /**
     * Total belanja dalam Rupiah, dihitung ulang dari harga produk terbaru
     * di database - lihat catatan keamanan di bagian atas file ini.
     */
    public function total(): int
    {
        return $this->isi()->sum('subtotal');
    }

    /**
     * Daftar ID produk yang sedang ada di keranjang. Dipakai RekomendasiService
     * supaya tidak menyarankan produk yang sudah dibeli.
     *
     * @return list<int>
     */
    public function idProduk(): array
    {
        return array_keys(Session::get(self::SESSION_KEY, []));
    }
}
