# Bengkel Balle Chery — Sistem Rekomendasi Sparepart (Laravel)

Ini adalah hasil migrasi penuh dari `bengkel_knn2` (PHP native + MySQLi) ke
Laravel 11, sekaligus perbaikan atas seluruh temuan di
`Laporan_Audit_Teknis_Bengkel_Balle_Chery.docx`. Aplikasi lama tetap
dibiarkan utuh di folder `../bengkel_knn2` sebagai arsip/pembanding.

## Menjalankan proyek ini

```bash
composer install
npm install && npm run build
cp .env.example .env   # lalu sesuaikan kredensial database jika perlu
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Buka `http://localhost:8000`. Login admin ada di `/login`:

- **Email:** `admin@ballechery.test`
- **Password:** `admin123` (ganti lewat menu Profil setelah login pertama kali)

> **Catatan lingkungan pengembangan (khusus mesin ini):** paket
> `laravel/framework` v11.46.1 yang terpasang ternyata tidak menyertakan
> `Foundation/resources/server.php` (kemungkinan celah packaging rilis
> tersebut). Sudah dibuatkan `server.php` pengganti di root proyek ini
> (Laravel resmi mendukung override lewat `base_path('server.php')`), jadi
> `php artisan serve` / `php -S` tetap berfungsi normal tanpa perlu
> mengubah apa pun lagi.

## Menjalankan tes otomatis

```bash
php artisan test
```

45 tes Feature mencakup seluruh alur (belanja, checkout, rating, admin
CRUD, keamanan login, RekomendasiService) — lihat folder `tests/Feature`.
Tes memakai database terpisah (`bengkel_laravel_test`, lihat `phpunit.xml`)
supaya tidak menyentuh data pengembangan.

## Struktur penting

| Bagian | Lokasi |
|---|---|
| Model & relasi | `app/Models` |
| Logika rekomendasi (KNN + co-purchase) | `app/Services/RekomendasiService.php` |
| Analisis popularitas stok (rule-based, bukan ML) | `app/Services/AnalisisStokService.php` |
| Keranjang belanja (session, harga selalu dari DB) | `app/Services/CartService.php` |
| Controller publik (toko) | `app/Http/Controllers/*.php` |
| Controller admin | `app/Http/Controllers/Admin/*.php` |
| Tampilan toko | `resources/views/storefront` |
| Tampilan admin | `resources/views/admin` |
| Komponen UI (tombol, kartu produk, ikon, dll) | `resources/views/components` |
| Perintah pembaruan rekomendasi terjadwal | `app/Console/Commands/PerbaruiRekomendasi.php`, dijadwalkan di `routes/console.php` |

## Pemetaan perbaikan dari Laporan Audit Teknis

| Temuan | Status | Bagaimana diperbaiki |
|---|---|---|
| F01/F02/F03 — SQL Injection (login, checkout, struk) | ✅ Selesai | Seluruh query lewat Eloquent/Query Builder (prepared statement otomatis). Tidak ada satu pun string SQL yang digabung manual dari input pengguna. |
| F04 — Password MD5 tanpa salt | ✅ Selesai | `App\Models\User` memakai cast `'password' => 'hashed'` bawaan Laravel (bcrypt). |
| F05 — Skor rekomendasi dari `rand()` | ✅ Selesai | `RekomendasiService` menghitung skor asli (Euclidean distance untuk KNN, frekuensi asli untuk co-purchase). Diverifikasi lewat `tests/Feature/RekomendasiServiceTest.php`. |
| F06 — Klaim "Akurasi 85-95%" statis | ✅ Selesai | Dihapus total, tidak digantikan angka karangan lain. Diuji lewat `AdminPagesTest::test_dashboard_tidak_menampilkan_klaim_akurasi_yang_tidak_pernah_dihitung`. |
| F07 — Skor bawaan 95% saat data kosong | ✅ Selesai | `RekomendasiService` mengembalikan koleksi kosong kalau data tidak cukup — tidak ada fallback angka. |
| F08 — "Analisis Stok KNN" bukan KNN | ✅ Selesai | Diganti nama jujur jadi "Analisis Popularitas Produk", dengan penjelasan eksplisit di halaman bahwa ini aturan ambang batas, bukan machine learning. |
| F09 — Tidak ada proteksi CSRF | ✅ Selesai | Middleware CSRF bawaan Laravel aktif di semua rute POST/PUT/PATCH/DELETE secara default. |
| F10 — `cron_update.php` publik tanpa autentikasi | ✅ Selesai | Diganti jadi perintah Artisan (`rekomendasi:perbarui`) yang cuma bisa dijalankan lewat command line/scheduler, sama sekali tidak ada di `routes/web.php`. |
| F11 — Rating bisa dipalsukan lewat ID tebakan | ✅ Selesai | Rating diakses lewat token acak 48 karakter (`rating_token`), bukan ID transaksi. |
| F12 — Output tidak di-escape (potensi XSS) | ✅ Selesai | Blade `{{ }}` meng-escape otomatis di semua tempat. |
| F13 — `install.php` tidak konsisten | ✅ Selesai | Migrasi Laravel jadi satu-satunya sumber kebenaran skema database. |
| F14 — Form produk tidak punya pilihan kategori | ✅ Selesai | Kategori jadi field wajib di form tambah/ubah produk, divalidasi lewat `StoreProdukRequest`/`UpdateProdukRequest`. |
| F15 — `default.png` tidak pernah ada | ✅ Selesai | `public/images/produk-default.svg` (ikon placeholder ringan) + accessor `Produk::gambar_url` yang otomatis memakainya. |
| F16 — Berkas debug (`test.php`, dll) | ✅ Selesai | Tidak ada berkas debug di aplikasi ini; `APP_DEBUG` dikendalikan lewat `.env` seperti semestinya. |
| *(Tambahan, ditemukan saat migrasi)* — harga produk dipercaya mentah dari klien saat checkout | ✅ Selesai | `CheckoutController` selalu membaca ulang `Produk::harga` dari database lewat `lockForUpdate()`, tidak pernah dari input klien. |

Perbaikan keamanan **tidak** diterapkan balik ke `bengkel_knn2` (folder PHP
lama) sesuai permintaan — folder itu dibiarkan sebagai bukti audit apa
adanya, dan laporan skripsi/audit tetap sah menjelaskan kondisinya.
