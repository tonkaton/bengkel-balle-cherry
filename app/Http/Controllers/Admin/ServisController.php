<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServisRequest;
use App\Models\Servis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Admin\ServisController - CRUD jasa servis dari panel admin (terpisah dari katalog sparepart). */
class ServisController extends Controller
{
    public function index(): View
    {
        $servis = Servis::withCount(['booking', 'detailTransaksi'])->orderBy('nama')->get();

        return view('admin.servis.index', ['servis' => $servis]);
    }

    public function create(): View
    {
        return view('admin.servis.form', ['servis' => new Servis(['estimasi_menit' => 60])]);
    }

    public function store(ServisRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['gambar'] = $this->simpanGambar($request);

        Servis::create($data);

        return redirect()->route('admin.servis.index')->with('sukses', 'Servis baru berhasil ditambahkan.');
    }

    public function edit(Servis $servis): View
    {
        return view('admin.servis.form', ['servis' => $servis]);
    }

    public function update(ServisRequest $request, Servis $servis): RedirectResponse
    {
        $data = $request->validated();

        if ($gambarBaru = $this->simpanGambar($request)) {
            $this->hapusGambarLama($servis);
            $data['gambar'] = $gambarBaru;
        }

        $servis->update($data);

        return redirect()->route('admin.servis.index')->with('sukses', 'Servis berhasil diperbarui.');
    }

    /** Servis yang sudah pernah dibooking atau masuk transaksi tidak boleh dihapus, supaya riwayatnya utuh. */
    public function destroy(Servis $servis): RedirectResponse
    {
        if ($servis->booking()->exists() || $servis->detailTransaksi()->exists()) {
            return back()->with('galat', "{$servis->nama} sudah pernah dibooking atau masuk transaksi, tidak bisa dihapus.");
        }

        $this->hapusGambarLama($servis);
        $servis->delete();

        return back()->with('sukses', "{$servis->nama} berhasil dihapus.");
    }

    private function simpanGambar(Request $request): ?string
    {
        return $request->hasFile('gambar') ? $request->file('gambar')->store('servis', 'public') : null;
    }

    private function hapusGambarLama(Servis $servis): void
    {
        if ($servis->gambar && Storage::disk('public')->exists($servis->gambar)) {
            Storage::disk('public')->delete($servis->gambar);
        }
    }
}
