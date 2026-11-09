# Panduan Deploy — Bengkel Balle Chery

Dokumen ini untuk tim DevOps yang memasang aplikasi ke server produksi.
Aplikasinya Laravel 11 monolit biasa (Blade + Vite) dengan satu database
MySQL. Tidak butuh Redis, queue worker, atau layanan eksternal lain.

## 1. Kebutuhan server

| Komponen | Versi / catatan |
|---|---|
| PHP | 8.2 atau lebih baru (dikembangkan di 8.2.14) |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `dom`, `ctype`, `fileinfo`, `curl`, `bcmath` |
| Database | MySQL 8.0 (dikembangkan di 8.0.30) atau MariaDB 10.6+; charset `utf8mb4`, collation `utf8mb4_unicode_ci` |
| Composer | 2.x |
| Node.js | 20 LTS atau lebih baru, **hanya** untuk build aset (`npm run build`); tidak dipakai saat runtime |
| Web server | Nginx + PHP-FPM atau Apache; document root **harus** `public/` |
| Cron | Wajib, untuk penjadwal Laravel (lihat langkah 9) |

Batas unggah: form produk menerima foto maks. 2 MB. Pastikan
`upload_max_filesize` ≥ 4M dan `post_max_size` ≥ 8M di `php.ini`.

## 2. Artefak dari developer (tidak ada di git)

Diserahkan terpisah dari repo, ada di folder `deploy/` milik developer:

| Berkas | Isi |
|---|---|
| `bengkel_laravel_2026-11-10.sql` | Dump database lengkap: 15 tabel, data produk, kategori, 18 transaksi, rating, log rekomendasi, akun admin. Tabel `sessions`/`cache` hanya strukturnya. Tanpa `CREATE DATABASE`, jadi bisa diimpor ke nama database apa pun. |
| `foto_produk.zip` | Folder `produk/` berisi foto produk yang diunggah admin (direferensikan oleh kolom `produk.gambar`). |

> **Jangan** menjalankan `php artisan migrate:fresh --seed` atau `db:seed` di
> produksi. Seeder membuat ulang produk dengan nama dan gambar contoh lama,
> sehingga nama dan foto yang sudah diganti admin akan hilang. Data produksi
> berasal dari dump SQL di atas.

## 3. Ambil kode

```bash
git clone https://github.com/tonkaton/bengkel-balle-cherry.git /var/www/bengkel
cd /var/www/bengkel
composer install --no-dev --optimize-autoloader
npm ci && npm run build        # menghasilkan public/build (di-.gitignore)
```

Kalau server tidak punya Node.js, jalankan `npm ci && npm run build` di
mesin lain lalu unggah folder `public/build/` ke server.

## 4. Konfigurasi `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Ubah nilai berikut (sisanya biarkan seperti di `.env.example`):

```dotenv
APP_ENV=production
APP_DEBUG=false                 # WAJIB false: kalau true, halaman error menampilkan kode & kredensial
APP_URL=https://domain-anda.com # pakai https, dipakai untuk link & URL gambar
APP_TIMEZONE=Asia/Jakarta

LOG_LEVEL=warning

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bengkel_laravel
DB_USERNAME=bengkel             # user khusus aplikasi, jangan root
DB_PASSWORD=********
```

Catatan:
- `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` sudah
  cukup untuk satu server; tidak perlu worker.
- `MAIL_MAILER=log`: fitur "Lupa password" tidak mengirim email sungguhan.
  Kalau mau dipakai, isi kredensial SMTP di bagian `MAIL_*`.
- Aplikasi sudah memercayai proxy (`trustProxies(at: '*')` di
  `bootstrap/app.php`), jadi aman di belakang load balancer / Cloudflare
  yang meneruskan HTTPS sebagai HTTP.

## 5. Database

```bash
mysql -u root -p -e "CREATE DATABASE bengkel_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'bengkel'@'localhost' IDENTIFIED BY '********';
  GRANT ALL PRIVILEGES ON bengkel_laravel.* TO 'bengkel'@'localhost';"
mysql -u bengkel -p bengkel_laravel < bengkel_laravel_2026-11-10.sql
php artisan migrate --force     # harus menjawab "Nothing to migrate"
```

Di shared hosting tanpa SSH: buat database lewat panel, lalu impor berkas
`.sql` lewat phpMyAdmin → Import.

## 6. Foto produk & storage

```bash
unzip foto_produk.zip -d storage/app/public/   # menghasilkan storage/app/public/produk/*
php artisan storage:link                       # public/storage -> storage/app/public
```

## 7. Hak akses berkas

Web server (contoh: `www-data`) harus bisa menulis ke `storage/` dan
`bootstrap/cache/`:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

## 8. Optimasi

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Setiap kali `.env` diubah, jalankan ulang `php artisan config:cache`.

## 9. Penjadwal (cron)

Rekomendasi produk dihitung ulang otomatis setiap hari pukul 00:30 WIB
(`rekomendasi:perbarui`, lihat `routes/console.php`). Tambahkan satu baris
cron untuk user web server:

```cron
* * * * * cd /var/www/bengkel && php artisan schedule:run >> /dev/null 2>&1
```

Tanpa cron, rekomendasi tetap bisa diperbarui manual dari panel admin
(menu Log Rekomendasi → Perbarui Sekarang).

## 10. Web server

Contoh Nginx (sesuaikan versi PHP-FPM dan domain):

```nginx
server {
    listen 80;
    server_name domain-anda.com;
    root /var/www/bengkel/public;
    index index.php;
    client_max_body_size 8M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

Pasang HTTPS (mis. `certbot --nginx -d domain-anda.com`). Untuk Apache,
arahkan `DocumentRoot` ke `public/`; berkas `public/.htaccess` bawaan
Laravel sudah mengatur rewrite.

## 11. Setelah deploy (wajib)

1. **Ganti password admin.** Akun di dump masih memakai password bawaan
   seeder. Login di `/login` sebagai `admin@ballechery.test`, buka menu
   Profil → Ubah Password. Minta password bawaannya ke developer secara
   langsung, jangan lewat tiket/chat publik.
2. Cek cepat:
   - `GET /up` → 200 (health check bawaan Laravel)
   - Halaman toko `/` menampilkan 7 produk beserta fotonya
   - Tambah produk ke keranjang → checkout → struk tampil
   - Panel admin: Dashboard, Produk, Log Rekomendasi bisa dibuka
3. Pastikan `APP_DEBUG=false`: buka URL acak seperti `/tidak-ada` → harus
   muncul halaman 404 biasa, bukan halaman error berwarna dengan stack trace.

## 12. Update rilis berikutnya

```bash
cd /var/www/bengkel
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

## 13. Backup

Data yang perlu dicadangkan hanya dua: database dan foto produk.

```cron
# setiap hari 02:00, simpan 14 hari terakhir
0 2 * * * mysqldump --single-transaction --no-tablespaces -u bengkel -p'********' bengkel_laravel | gzip > /backup/bengkel_$(date +\%F).sql.gz && find /backup -name 'bengkel_*.sql.gz' -mtime +14 -delete
0 2 * * * tar czf /backup/foto_$(date +\%F).tgz -C /var/www/bengkel/storage/app/public produk
```

Pulihkan dengan `gunzip -c berkas.sql.gz | mysql -u bengkel -p bengkel_laravel`.

## 14. Masalah yang sering muncul

| Gejala | Penyebab & solusi |
|---|---|
| Foto produk tidak muncul (404) | `php artisan storage:link` belum dijalankan, atau `foto_produk.zip` belum diekstrak ke `storage/app/public/` |
| Tampilan berantakan tanpa CSS | `public/build/` belum ada → jalankan `npm run build` |
| Error 500, log berisi "Permission denied" | Hak akses `storage/` dan `bootstrap/cache/` (langkah 7) |
| Error 419 "Page Expired" saat submit form | Sesi tidak tersimpan (cek hak akses `storage/framework/sessions`) atau `APP_URL`/domain tidak sesuai |
| Form/link mengarah ke `http://` padahal situs `https://` | `APP_URL` belum memakai `https://`, lalu `php artisan config:cache` |
| Unggah foto gagal tanpa pesan | `upload_max_filesize`/`post_max_size` PHP atau `client_max_body_size` Nginx terlalu kecil |
| Perubahan `.env` tidak berpengaruh | Jalankan `php artisan config:clear && php artisan config:cache` |
| Log aplikasi | `storage/logs/laravel.log` |
