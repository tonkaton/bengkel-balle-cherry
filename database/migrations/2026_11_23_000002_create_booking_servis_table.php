<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Booking servis dari pelanggan (tanpa akun, cukup nama dan nomor HP).
     * Status: menunggu -> dikonfirmasi -> selesai, atau batal. Saat booking
     * diselesaikan admin, transaksi_id terisi dengan transaksi yang memuat
     * servis tersebut beserta sparepart yang dipakai.
     */
    public function up(): void
    {
        Schema::create('booking_servis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_booking', 20)->unique();
            $table->foreignId('servis_id')->constrained('servis')->restrictOnDelete();
            $table->string('nama_pelanggan', 100);
            $table->string('no_hp', 20);
            $table->string('kendaraan', 100);
            $table->string('plat_nomor', 15);
            $table->date('tanggal');
            $table->time('jam');
            $table->text('keluhan')->nullable();
            $table->string('status', 20)->default('menunggu')->index();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksi')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_servis');
    }
};
