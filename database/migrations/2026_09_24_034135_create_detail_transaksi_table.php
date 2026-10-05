<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bikin tabel "detail_transaksi" - daftar item per transaksi.
     *
     * Catatan penting: kolom "harga_saat_beli" WAJIB diisi dari harga produk
     * yang tersimpan di database pada saat checkout diproses di server,
     * BUKAN dari angka yang dikirim browser. Versi PHP lama mempercayai
     * begitu saja harga kiriman klien (lihat CheckoutController untuk
     * penjelasan lengkap kenapa ini krusial) - itu celah manipulasi harga
     * yang tidak sempat tercatat di laporan audit sebelumnya, tapi tetap
     * diperbaiki di sini karena termasuk pelajaran yang sama: jangan pernah
     * percaya data dari klien untuk sesuatu yang menyangkut uang.
     */
    public function up(): void
    {
        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->unsignedInteger('jumlah');
            $table->unsignedBigInteger('harga_saat_beli');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_transaksi');
    }
};
