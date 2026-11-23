<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Servis keluar dari tabel produk:
     * 1. detail_transaksi bisa berisi sparepart (produk_id) ATAU servis
     *    (servis_id), supaya transaksi hasil booking tercatat dan ikut
     *    dihitung KNN.
     * 2. log_rekomendasi memakai pasangan jenis+id (polimorfik), karena item
     *    asal maupun item yang direkomendasikan bisa produk atau servis.
     * 3. Produk berkategori "Jasa Servis" (cara lama) dipindahkan ke tabel
     *    servis beserta riwayat transaksi dan log-nya, lalu kategori itu
     *    dihapus.
     */
    private const DESKRIPSI = [
        'Servis Ringan' => ['Penggantian oli, pembersihan filter udara dan CVT, serta pengecekan rem, busi, dan lampu. Harga jasa saja, belum termasuk sparepart.', 60],
        'Servis Besar' => ['Pemeriksaan mesin menyeluruh, termasuk pembersihan ruang bakar dan skir klep. Harga jasa saja, belum termasuk sparepart.', 180],
    ];

    public function up(): void
    {
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->dropForeign(['produk_id']);
        });
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->unsignedBigInteger('produk_id')->nullable()->change();
            $table->foreign('produk_id')->references('id')->on('produk')->cascadeOnDelete();
            $table->foreignId('servis_id')->nullable()->after('produk_id')->constrained('servis')->restrictOnDelete();
        });

        Schema::table('log_rekomendasi', function (Blueprint $table) {
            $table->string('asal_type', 20)->default('produk')->after('id');
            $table->unsignedBigInteger('asal_id')->nullable()->after('asal_type');
            $table->string('rekomendasi_type', 20)->default('produk')->after('asal_id');
            $table->unsignedBigInteger('rekomendasi_id')->nullable()->after('rekomendasi_type');
        });
        DB::table('log_rekomendasi')->update([
            'asal_id' => DB::raw('produk_asal_id'),
            'rekomendasi_id' => DB::raw('produk_rekomendasi_id'),
        ]);
        Schema::table('log_rekomendasi', function (Blueprint $table) {
            $table->dropForeign(['produk_asal_id']);
            $table->dropForeign(['produk_rekomendasi_id']);
            $table->dropColumn(['produk_asal_id', 'produk_rekomendasi_id']);
            $table->index(['asal_type', 'asal_id']);
        });

        $kategoriJasa = DB::table('kategori')->where('nama', 'Jasa Servis')->value('id');
        if (! $kategoriJasa) {
            return;
        }

        foreach (DB::table('produk')->where('kategori_id', $kategoriJasa)->get() as $produk) {
            [$deskripsi, $menit] = self::DESKRIPSI[$produk->nama] ?? [null, 60];
            $servisId = DB::table('servis')->insertGetId([
                'nama' => $produk->nama,
                'deskripsi' => $deskripsi,
                'harga' => $produk->harga,
                'estimasi_menit' => $menit,
                'gambar' => $produk->gambar,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('detail_transaksi')->where('produk_id', $produk->id)->update(['produk_id' => null, 'servis_id' => $servisId]);
            foreach (['asal', 'rekomendasi'] as $sisi) {
                DB::table('log_rekomendasi')->where("{$sisi}_type", 'produk')->where("{$sisi}_id", $produk->id)
                    ->update(["{$sisi}_type" => 'servis', "{$sisi}_id" => $servisId]);
            }
            DB::table('produk')->where('id', $produk->id)->delete();
        }

        DB::table('kategori')->where('id', $kategoriJasa)->delete();
    }

    /** Kembalikan servis menjadi produk berkategori "Jasa Servis" (stok 999), seperti sebelum migrasi ini. */
    public function down(): void
    {
        $kategoriJasa = DB::table('kategori')->where('nama', 'Jasa Servis')->value('id')
            ?? DB::table('kategori')->insertGetId(['nama' => 'Jasa Servis', 'created_at' => now(), 'updated_at' => now()]);

        foreach (DB::table('servis')->get() as $servis) {
            $produkId = DB::table('produk')->insertGetId([
                'barcode' => 'SRV-'.$servis->id,
                'nama' => $servis->nama,
                'harga' => $servis->harga,
                'stok' => 999,
                'kategori_id' => $kategoriJasa,
                'gambar' => $servis->gambar,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('detail_transaksi')->where('servis_id', $servis->id)->update(['produk_id' => $produkId, 'servis_id' => null]);
            foreach (['asal', 'rekomendasi'] as $sisi) {
                DB::table('log_rekomendasi')->where("{$sisi}_type", 'servis')->where("{$sisi}_id", $servis->id)
                    ->update(["{$sisi}_type" => 'produk', "{$sisi}_id" => $produkId]);
            }
        }

        Schema::table('log_rekomendasi', function (Blueprint $table) {
            $table->foreignId('produk_asal_id')->nullable()->after('id');
            $table->foreignId('produk_rekomendasi_id')->nullable()->after('produk_asal_id');
        });
        DB::table('log_rekomendasi')->update([
            'produk_asal_id' => DB::raw('asal_id'),
            'produk_rekomendasi_id' => DB::raw('rekomendasi_id'),
        ]);
        Schema::table('log_rekomendasi', function (Blueprint $table) {
            $table->dropIndex(['asal_type', 'asal_id']);
            $table->dropColumn(['asal_type', 'asal_id', 'rekomendasi_type', 'rekomendasi_id']);
            $table->foreign('produk_asal_id')->references('id')->on('produk')->cascadeOnDelete();
            $table->foreign('produk_rekomendasi_id')->references('id')->on('produk')->cascadeOnDelete();
        });

        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('servis_id');
        });
    }
};
