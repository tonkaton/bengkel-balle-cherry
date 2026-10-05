<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CartController - semua aksi yang mengubah isi keranjang belanja.
 *
 * Semua method di sini sengaja redirect()->back() alih-alih membalas JSON.
 * Ini pilihan sadar, bukan kelupaan: keranjang disimpan di session server
 * (lihat CartService), jadi begitu redirect kembali ke halaman, Blade akan
 * merender ulang keranjang langsung dari session - tidak perlu JavaScript
 * rumit untuk menyinkronkan state antara browser dan server, dan tidak ada
 * celah untuk data yang "berbeda" antara apa yang dilihat pelanggan dengan
 * apa yang sebenarnya tersimpan.
 */
class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    /**
     * Tambah satu produk ke keranjang lewat tombol "Tambah ke Keranjang".
     * Jumlah otomatis dibatasi maksimal sebanyak stok yang tersedia supaya
     * pelanggan tidak bisa memesan lebih banyak dari yang ada di rak.
     */
    public function tambah(Request $request, Produk $produk): RedirectResponse
    {
        if ($produk->stok_habis) {
            return back()->with('galat', "Maaf, stok {$produk->nama} sedang habis.");
        }

        $this->cart->tambah($produk->id, 1);

        return back()->with('sukses', "{$produk->nama} ditambahkan ke keranjang.");
    }

    /**
     * Cari produk lewat barcode (dari alat scanner atau diketik manual di
     * kolom pencarian atas), lalu langsung tambahkan ke keranjang kalau
     * ditemukan - meniru alur kasir sungguhan.
     */
    public function scan(Request $request): RedirectResponse
    {
        $request->validate(['barcode' => ['required', 'string', 'max:50']]);

        $produk = Produk::where('barcode', $request->string('barcode'))->first();

        if (! $produk) {
            return back()->with('galat', 'Produk dengan barcode tersebut tidak ditemukan.');
        }

        if ($produk->stok_habis) {
            return back()->with('galat', "Maaf, stok {$produk->nama} sedang habis.");
        }

        $this->cart->tambah($produk->id, 1);

        return back()->with('sukses', "{$produk->nama} ditambahkan ke keranjang.");
    }

    /**
     * Ubah jumlah salah satu item di keranjang (tombol + / - pada
     * halaman keranjang).
     */
    public function ubah(Request $request, Produk $produk): RedirectResponse
    {
        $data = $request->validate(['jumlah' => ['required', 'integer', 'min:0', 'max:'.max(1, $produk->stok)]]);

        $this->cart->ubahJumlah($produk->id, $data['jumlah']);

        return back();
    }

    /** Keluarkan satu produk dari keranjang (tombol tempat sampah). */
    public function hapus(Produk $produk): RedirectResponse
    {
        $this->cart->hapus($produk->id);

        return back()->with('sukses', "{$produk->nama} dikeluarkan dari keranjang.");
    }

    /** Kosongkan seluruh isi keranjang. */
    public function kosongkan(): RedirectResponse
    {
        $this->cart->kosongkan();

        return back()->with('sukses', 'Keranjang dikosongkan.');
    }
}
