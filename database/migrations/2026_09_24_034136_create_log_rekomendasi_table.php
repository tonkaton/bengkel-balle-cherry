<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bikin tabel "log_rekomendasi" - riwayat rekomendasi yang pernah
     * dihasilkan sistem.
     *
     * Perbaikan dibanding versi lama (Laporan Audit Teknis temuan F05):
     * kolom "skor" sekarang WAJIB berisi angka yang benar-benar dihitung oleh
     * RekomendasiService, bukan rand(75,98) seperti dulu. Ditambahkan juga
     * kolom "metode" supaya siapa pun yang membuka tabel ini bisa langsung
     * tahu rekomendasi itu berasal dari perhitungan K-Nearest Neighbor
     * (kemiripan rating antar pembeli) atau dari pola pembelian bersamaan
     * (co-purchase) - dua hal ini memang secara sengaja dibedakan, bukan
     * disamaratakan sebagai "KNN" seperti versi PHP lama.
     */
    public function up(): void
    {
        Schema::create('log_rekomendasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_asal_id')->constrained('produk')->cascadeOnDelete();
            $table->foreignId('produk_rekomendasi_id')->constrained('produk')->cascadeOnDelete();
            $table->decimal('skor', 5, 2);
            $table->string('metode', 30)->default('co_purchase');
            $table->timestamp('tanggal')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_rekomendasi');
    }
};
