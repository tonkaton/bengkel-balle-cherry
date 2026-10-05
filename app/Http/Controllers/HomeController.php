<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;
use App\Services\CartService;
use App\Services\RekomendasiService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * HomeController - halaman utama toko (storefront) yang dilihat pelanggan.
 *
 * Cuma satu tugas di sini: menampilkan katalog produk beserta filter
 * kategori dan kotak pencarian, plus kotak rekomendasi yang mengikuti isi
 * keranjang. Semua logika "berat" (hitung rekomendasi, kelola keranjang)
 * sengaja dilempar ke Service supaya controller ini tetap tipis dan mudah
 * dibaca.
 */
class HomeController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly RekomendasiService $rekomendasi,
    ) {}

    /**
     * Tampilkan katalog produk. Mendukung dua query string opsional:
     * ?kategori={id} untuk filter kategori, dan ?cari={kata kunci} untuk
     * pencarian nama/barcode - keduanya bisa dipakai bersamaan.
     *
     * Daftar produk di-paginate (bukan ditampilkan semua sekaligus) supaya
     * halaman tetap ringan walau jumlah produk sudah ratusan - ini salah
     * satu alasan versi Laravel ini dijamin tidak melambat seiring
     * bertambahnya data, beda dengan versi lama yang memuat seluruh tabel
     * produk sekaligus di setiap kunjungan halaman.
     */
    public function index(Request $request): View
    {
        $produk = Produk::query()
            ->with('kategori') // eager load, supaya tidak query berulang per baris (N+1) saat Blade menampilkan nama kategori
            ->when($request->filled('kategori'), fn ($query) => $query->where('kategori_id', $request->integer('kategori')))
            ->when($request->filled('cari'), function ($query) use ($request) {
                $kataKunci = $request->string('cari');
                $query->where(fn ($sub) => $sub
                    ->where('nama', 'like', "%{$kataKunci}%")
                    ->orWhere('barcode', 'like', "%{$kataKunci}%"));
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $kategori = Kategori::withCount('produk')->orderBy('nama')->get();

        $isiKeranjang = $this->cart->isi();
        $rekomendasi = $isiKeranjang->isNotEmpty()
            ? $this->rekomendasi->rekomendasikanUntukKeranjang($this->cart->idProduk(), 3)
            : collect();

        return view('storefront.index', [
            'produk' => $produk,
            'kategori' => $kategori,
            'kategoriAktif' => $request->integer('kategori') ?: null,
            'kataKunci' => $request->string('cari')->toString(),
            'keranjang' => $isiKeranjang,
            'totalKeranjang' => $this->cart->total(),
            'rekomendasi' => $rekomendasi,
        ]);
    }
}
