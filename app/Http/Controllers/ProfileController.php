<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * ProfileController - halaman admin mengubah nama, email, dan password
 * akunnya sendiri.
 *
 * Catatan: method destroy() bawaan Laravel Breeze (hapus akun sendiri)
 * SENGAJA dihilangkan dari sini. Aplikasi ini cuma punya satu akun admin;
 * kalau fitur itu dibiarkan aktif dan tombolnya tidak sengaja terklik,
 * satu-satunya jalan masuk ke panel admin akan hilang tanpa cara mudah
 * untuk membuatnya lagi selain lewat command line.
 */
class ProfileController extends Controller
{
    /** Tampilkan form profil, sudah terisi nama & email admin yang sedang login. */
    public function edit(Request $request): View
    {
        return view('admin.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /** Simpan perubahan nama/email. Ubah password ditangani terpisah lewat PasswordController bawaan Breeze. */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        return Redirect::route('admin.profile.edit')->with('sukses', 'Profil berhasil diperbarui.');
    }
}
