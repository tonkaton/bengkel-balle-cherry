<?php

use App\Http\Controllers\Admin\AnalisisStokController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\ProdukController as AdminProdukController;
use App\Http\Controllers\Admin\RatingController as AdminRatingController;
use App\Http\Controllers\Admin\RekomendasiController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\StrukController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute publik (storefront) - bisa diakses siapa saja tanpa login
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('keranjang')->name('keranjang.')->group(function () {
    Route::post('scan', [CartController::class, 'scan'])->name('scan');
    Route::post('{produk}', [CartController::class, 'tambah'])->name('tambah');
    Route::patch('{produk}', [CartController::class, 'ubah'])->name('ubah');
    Route::delete('{produk}', [CartController::class, 'hapus'])->name('hapus');
    Route::post('/', [CartController::class, 'kosongkan'])->name('kosongkan');
});

Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// Route model binding lewat kolom kode_transaksi (bukan id mentah) - lihat
// catatan lengkap soal perbaikan SQL Injection F03 di StrukController.
Route::get('/struk/{transaksi:kode_transaksi}', [StrukController::class, 'show'])->name('struk.show');

// Route model binding lewat kolom rating_token, bukan id transaksi yang
// gampang ditebak - lihat catatan lengkap soal perbaikan F11 di
// RatingController.
Route::get('/rating/{transaksi:rating_token}', [RatingController::class, 'create'])->name('rating.create');
Route::post('/rating/{transaksi:rating_token}', [RatingController::class, 'store'])->name('rating.store');

/*
|--------------------------------------------------------------------------
| Rute admin - seluruhnya wajib login (middleware "auth" bawaan Breeze)
|--------------------------------------------------------------------------
|
| Karena hanya ada satu peran pengguna di aplikasi ini (admin), middleware
| "auth" saja sudah cukup - tidak perlu sistem role/permission yang rumit
| untuk kebutuhan yang sebenarnya sederhana ini.
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('produk', AdminProdukController::class)->except(['show']);

    Route::get('kategori', [KategoriController::class, 'index'])->name('kategori.index');
    Route::post('kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::delete('kategori/{kategori}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

    Route::get('rating', [AdminRatingController::class, 'index'])->name('rating.index');
    Route::delete('rating/{rating}', [AdminRatingController::class, 'destroy'])->name('rating.destroy');

    Route::get('analisis-stok', [AnalisisStokController::class, 'index'])->name('analisis-stok.index');
    Route::post('analisis-stok/{produk}', [AnalisisStokController::class, 'updateStok'])->name('analisis-stok.update');

    Route::get('rekomendasi', [RekomendasiController::class, 'index'])->name('rekomendasi.index');
    Route::post('rekomendasi/perbarui', [RekomendasiController::class, 'perbarui'])->name('rekomendasi.perbarui');

    Route::get('export/transaksi', [ExportController::class, 'transaksi'])->name('export.transaksi');
    Route::get('export/produk', [ExportController::class, 'produk'])->name('export.produk');
    Route::get('export/rekomendasi', [ExportController::class, 'rekomendasi'])->name('export.rekomendasi');

    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profil', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
