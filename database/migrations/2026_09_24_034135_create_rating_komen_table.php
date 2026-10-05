<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bikin tabel "rating_komen".
     *
     * Satu transaksi cuma boleh dinilai sekali - makanya transaksi_id diberi
     * UNIQUE, persis seperti aturan bisnisnya di RatingController.
     */
    public function up(): void
    {
        Schema::create('rating_komen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->unique()->constrained('transaksi')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('komen')->nullable();
            $table->timestamp('tanggal')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_komen');
    }
};
