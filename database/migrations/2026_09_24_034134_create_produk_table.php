<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bikin tabel "produk".
     *
     * Catatan perbaikan dibanding versi PHP lama (lihat Laporan Audit Teknis
     * temuan F14-F15): kolom kategori_id sekarang benar-benar dipakai lewat
     * form admin (dulu kolom ini ada di database tapi form tambah produknya
     * tidak punya pilihan kategori sama sekali, jadi selalu NULL). Kolom
     * "gambar" juga tidak lagi mengandalkan file default.png yang ternyata
     * tidak pernah benar-benar ada di folder uploads - sekarang kalau kosong,
     * tampilan cukup menampilkan ikon placeholder bawaan tanpa perlu file
     * fisik apa pun.
     */
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 50)->unique();
            $table->string('nama', 150);
            $table->unsignedBigInteger('harga');
            $table->unsignedInteger('stok')->default(0);
            $table->foreignId('kategori_id')->nullable()->constrained('kategori')->nullOnDelete();
            $table->string('gambar')->nullable();
            $table->timestamps();

            // Barcode sering dipakai untuk pencarian cepat di kasir, jadi
            // diberi index eksplisit (walau UNIQUE sudah otomatis bikin index,
            // ini ditulis biar jelas alasannya buat siapa pun yang baca skema).
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
