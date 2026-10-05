<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bikin tabel "transaksi".
     *
     * Perbaikan penting dibanding versi lama (Laporan Audit Teknis temuan
     * F11): dulu siapa saja bisa mengirim rating atas nama transaksi orang
     * lain karena transaksi_id cuma angka urut yang gampang ditebak (1, 2, 3,
     * ...). Di sini ditambahkan kolom "rating_token" - kode acak panjang yang
     * dibuat sekali saat checkout dan dikembalikan ke pembeli lewat sesi
     * browser. Kode inilah yang jadi syarat wajib saat mengirim rating,
     * bukan sekadar ID transaksi yang mudah ditebak.
     */
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi', 50)->unique();
            $table->unsignedBigInteger('total_harga');
            $table->string('rating_token', 64)->unique();
            $table->timestamp('tanggal')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
