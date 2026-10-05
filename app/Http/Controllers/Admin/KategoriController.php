<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin\KategoriController - kelola daftar kategori sparepart.
 *
 * Fitur ini SAMA SEKALI TIDAK ADA di panel admin versi PHP lama - kategori
 * dulu cuma bisa diisi lewat database.sql sekali saat instalasi, tidak bisa
 * ditambah lagi lewat aplikasi. Di sini ditambahkan sebagai pelengkap wajar
 * supaya form tambah produk (yang sekarang mewajibkan kategori, lihat
 * temuan F14) tidak membuat admin terjebak kalau suatu saat butuh kategori
 * baru yang belum ada.
 *
 * Sengaja dibuat sederhana (cuma tambah & hapus, tanpa halaman ubah
 * terpisah) karena memang cuma itu yang benar-benar dibutuhkan - menambah
 * fitur "ubah nama kategori" sekarang hanya akan jadi kode yang tidak
 * pernah dipakai.
 */
class KategoriController extends Controller
{
    /** Tampilkan daftar kategori beserta jumlah produk di masing-masing kategori. */
    public function index(): View
    {
        return view('admin.kategori.index', [
            'kategori' => Kategori::withCount('produk')->orderBy('nama')->get(),
        ]);
    }

    /** Tambah kategori baru (namanya harus unik). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['nama' => ['required', 'string', 'max:50', 'unique:kategori,nama']]);

        Kategori::create($data);

        return back()->with('sukses', 'Kategori baru berhasil ditambahkan.');
    }

    /** Hapus kategori - ditolak kalau masih ada produk yang memakainya. */
    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->produk()->exists()) {
            return back()->with('galat', "Kategori \"{$kategori->nama}\" masih dipakai oleh produk lain, tidak bisa dihapus.");
        }

        $kategori->delete();

        return back()->with('sukses', 'Kategori berhasil dihapus.');
    }
}
