<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProdukRequest;
use App\Http\Requests\Admin\UpdateProdukRequest;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin\ProdukController - CRUD produk dari panel admin.
 *
 * Semua query di sini lewat Eloquent, jadi otomatis pakai prepared
 * statement (bukan penggabungan string SQL mentah seperti versi lama -
 * lihat Laporan Audit Teknis temuan F01-F03 soal SQL Injection).
 */
class ProdukController extends Controller
{
    /** Tabel produk admin, dengan pencarian nama/barcode dan pagination (bukan dimuat sekaligus semua). */
    public function index(Request $request): View
    {
        $produk = Produk::query()
            ->with('kategori')
            ->when($request->filled('cari'), function ($query) use ($request) {
                $kataKunci = $request->string('cari');
                $query->where(fn ($sub) => $sub
                    ->where('nama', 'like', "%{$kataKunci}%")
                    ->orWhere('barcode', 'like', "%{$kataKunci}%"));
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.produk.index', ['produk' => $produk, 'kataKunci' => $request->string('cari')->toString()]);
    }

    /** Tampilkan form tambah produk kosong (produk baru belum ada di database, jadi cuma object kosong). */
    public function create(): View
    {
        return view('admin.produk.form', ['produk' => new Produk, 'kategori' => Kategori::orderBy('nama')->get()]);
    }

    /** Simpan produk baru - lihat StoreProdukRequest untuk aturan validasinya (termasuk kategori wajib, perbaikan F14). */
    public function store(StoreProdukRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['gambar'] = $this->simpanGambar($request);

        Produk::create($data);

        return redirect()->route('admin.produk.index')->with('sukses', 'Produk baru berhasil ditambahkan.');
    }

    /** Tampilkan form ubah, sudah terisi data produk yang dipilih. */
    public function edit(Produk $produk): View
    {
        return view('admin.produk.form', ['produk' => $produk, 'kategori' => Kategori::orderBy('nama')->get()]);
    }

    /** Simpan perubahan produk; gambar lama cuma dihapus kalau ada gambar baru yang diunggah. */
    public function update(UpdateProdukRequest $request, Produk $produk): RedirectResponse
    {
        $data = $request->validated();

        if ($gambarBaru = $this->simpanGambar($request)) {
            $this->hapusGambarLama($produk);
            $data['gambar'] = $gambarBaru;
        }

        $produk->update($data);

        return redirect()->route('admin.produk.index')->with('sukses', 'Produk berhasil diperbarui.');
    }

    /**
     * Hapus produk - tapi cuma kalau produk itu belum pernah muncul di
     * transaksi mana pun. Aturan ini dipertahankan dari versi lama karena
     * memang masuk akal: menghapus produk yang sudah pernah dibeli akan
     * merusak riwayat transaksi/struk pelanggan yang sudah ada.
     */
    public function destroy(Produk $produk): RedirectResponse
    {
        if ($produk->detailTransaksi()->exists()) {
            return back()->with('galat', "{$produk->nama} sudah pernah terjual, tidak bisa dihapus. Ubah stoknya menjadi 0 jika ingin menghentikan penjualan.");
        }

        $this->hapusGambarLama($produk);
        $produk->delete();

        return back()->with('sukses', "{$produk->nama} berhasil dihapus.");
    }

    /**
     * Simpan file gambar yang diunggah (kalau ada) ke disk "public" dengan
     * nama acak, dan kembalikan path relatifnya untuk disimpan di kolom
     * "gambar". Kalau admin tidak mengunggah file baru, fungsi ini
     * mengembalikan null dan kolom gambar yang lama tidak diutak-atik.
     */
    private function simpanGambar(Request $request): ?string
    {
        if (! $request->hasFile('gambar')) {
            return null;
        }

        return $request->file('gambar')->store('produk', 'public');
    }

    /** Hapus file foto lama dari disk supaya folder storage tidak menumpuk file yatim yang tidak terpakai lagi. */
    private function hapusGambarLama(Produk $produk): void
    {
        if ($produk->gambar && Storage::disk('public')->exists($produk->gambar)) {
            Storage::disk('public')->delete($produk->gambar);
        }
    }
}
