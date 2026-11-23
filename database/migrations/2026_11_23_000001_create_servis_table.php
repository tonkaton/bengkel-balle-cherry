<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jasa servis (Servis Ringan, Servis Besar, ...) punya tabel sendiri,
     * terpisah dari produk: servis tidak punya stok, barcode, maupun
     * kategori sparepart, tapi punya estimasi lama pengerjaan dan dipesan
     * lewat booking.
     */
    public function up(): void
    {
        Schema::create('servis', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('harga');
            $table->unsignedSmallInteger('estimasi_menit')->default(60);
            $table->string('gambar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servis');
    }
};
