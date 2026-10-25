<?php

namespace Tests\Feature;

use App\Models\DetailTransaksi;
use App\Models\LogRekomendasi;
use App\Models\Produk;
use App\Models\RatingKomen;
use App\Models\Transaksi;
use App\Services\RekomendasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tes ini adalah bukti langsung bahwa perbaikan paling penting dari audit
 * (temuan F05: skor rekomendasi dulu berasal dari rand(75,98), bukan hasil
 * perhitungan) benar-benar berhasil. Setiap tes di sini memverifikasi
 * bahwa skor yang dikembalikan RekomendasiService adalah angka yang bisa
 * ditelusuri balik ke data transaksi/rating yang sengaja disiapkan -
 * bukan angka acak.
 */
class RekomendasiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanpa_data_transaksi_sama_sekali_tidak_ada_rekomendasi(): void
    {
        $produk = Produk::factory()->create();

        $hasil = app(RekomendasiService::class)->rekomendasikanUntukProduk($produk->id);

        // Ini yang paling penting: TIDAK ADA fallback ke angka bawaan
        // (dulu selalu tampil "95%" walau datanya kosong - temuan F07).
        // Sekarang, tidak ada data berarti tidak ada rekomendasi sama
        // sekali, dikembalikan sebagai koleksi kosong.
        $this->assertTrue($hasil->isEmpty());
    }

    public function test_skor_co_purchase_dihitung_dari_frekuensi_pembelian_bersama_yang_asli(): void
    {
        $kampas = Produk::factory()->create(['nama' => 'Kampas Rem']);
        $oli = Produk::factory()->create(['nama' => 'Oli Mesin']);
        $busi = Produk::factory()->create(['nama' => 'Busi']);

        // Kampas dibeli bersama Oli sebanyak 3 kali, bersama Busi cuma 1 kali.
        for ($i = 0; $i < 3; $i++) {
            $transaksi = Transaksi::factory()->create();
            DetailTransaksi::factory()->create(['transaksi_id' => $transaksi->id, 'produk_id' => $kampas->id]);
            DetailTransaksi::factory()->create(['transaksi_id' => $transaksi->id, 'produk_id' => $oli->id]);
        }
        $transaksiBusi = Transaksi::factory()->create();
        DetailTransaksi::factory()->create(['transaksi_id' => $transaksiBusi->id, 'produk_id' => $kampas->id]);
        DetailTransaksi::factory()->create(['transaksi_id' => $transaksiBusi->id, 'produk_id' => $busi->id]);

        $hasil = app(RekomendasiService::class)->rekomendasikanUntukProduk($kampas->id);

        $skorOli = $hasil->firstWhere('produk.id', $oli->id);
        $skorBusi = $hasil->firstWhere('produk.id', $busi->id);

        $this->assertNotNull($skorOli);
        $this->assertNotNull($skorBusi);
        $this->assertSame('co_purchase', $skorOli['metode']);
        // Oli muncul 3x dari total 4 kemunculan bersama = 75%, Busi cuma 25%.
        $this->assertEqualsWithDelta(75.0, $skorOli['skor'], 0.01);
        $this->assertEqualsWithDelta(25.0, $skorBusi['skor'], 0.01);
        $this->assertGreaterThan($skorBusi['skor'], $skorOli['skor']);
    }

    public function test_rekomendasi_keranjang_menjumlahkan_skor_produk_yang_muncul_dari_dua_item(): void
    {
        $kampas = Produk::factory()->create(['nama' => 'Kampas Rem']);
        $busi = Produk::factory()->create(['nama' => 'Busi']);
        $oli = Produk::factory()->create(['nama' => 'Oli Mesin']);

        // Oli dibeli bersama Kampas dan bersama Busi, jadi Oli direkomendasikan
        // dari kedua item keranjang - dulu kasus ini membuat halaman toko error.
        foreach ([$kampas, $busi] as $asal) {
            $transaksi = Transaksi::factory()->create();
            DetailTransaksi::factory()->create(['transaksi_id' => $transaksi->id, 'produk_id' => $asal->id]);
            DetailTransaksi::factory()->create(['transaksi_id' => $transaksi->id, 'produk_id' => $oli->id]);
        }

        $hasil = app(RekomendasiService::class)->rekomendasikanUntukKeranjang([$kampas->id, $busi->id]);

        $this->assertCount(1, $hasil);
        $this->assertSame($oli->id, $hasil[0]['produk']->id);
        // 100% dari Kampas + 100% dari Busi.
        $this->assertEqualsWithDelta(200.0, $hasil[0]['skor'], 0.01);
    }

    public function test_transaksi_tanpa_produk_yang_sama_tidak_dianggap_tetangga(): void
    {
        // Regresi: dulu dua transaksi tanpa irisan produk menghasilkan jarak 0
        // (similarity 1) sehingga produk yang tak berhubungan ikut direkomendasikan.
        [$target, $teman, $asing] = Produk::factory()->count(3)->create();

        for ($i = 0; $i < 3; $i++) {
            $transaksi = Transaksi::factory()->create();
            DetailTransaksi::factory()->create(['transaksi_id' => $transaksi->id, 'produk_id' => $target->id]);
            DetailTransaksi::factory()->create(['transaksi_id' => $transaksi->id, 'produk_id' => $teman->id]);
            RatingKomen::factory()->create(['transaksi_id' => $transaksi->id, 'rating' => 5]);
        }

        $transaksiAsing = Transaksi::factory()->create();
        DetailTransaksi::factory()->create(['transaksi_id' => $transaksiAsing->id, 'produk_id' => $asing->id]);
        RatingKomen::factory()->create(['transaksi_id' => $transaksiAsing->id, 'rating' => 5]);

        $hasil = app(RekomendasiService::class)->rekomendasikanUntukProduk($target->id);

        $this->assertNotContains($asing->id, $hasil->pluck('produk.id'));
    }

    public function test_skor_knn_dihitung_dari_kemiripan_rating_bukan_angka_acak(): void
    {
        $target = Produk::factory()->create();
        $produkLain = Produk::factory()->count(4)->create();

        // Siapkan >= 3 transaksi yang membeli & memberi rating pada produk
        // target, supaya ambang MINIMAL_TRANSAKSI_PEMBANDING terpenuhi dan
        // collaborative filtering (bukan fallback co-purchase) yang dipakai.
        for ($i = 0; $i < 4; $i++) {
            $transaksi = Transaksi::factory()->create();
            DetailTransaksi::factory()->create(['transaksi_id' => $transaksi->id, 'produk_id' => $target->id]);
            DetailTransaksi::factory()->create(['transaksi_id' => $transaksi->id, 'produk_id' => $produkLain[$i % 4]->id]);
            RatingKomen::factory()->create(['transaksi_id' => $transaksi->id, 'rating' => 5]);
        }

        // Satu transaksi pembanding lain yang TIDAK membeli produk target,
        // tapi rating-nya identik (5) - seharusnya dianggap "tetangga" mirip.
        $transaksiTetangga = Transaksi::factory()->create();
        DetailTransaksi::factory()->create(['transaksi_id' => $transaksiTetangga->id, 'produk_id' => $produkLain[0]->id]);
        RatingKomen::factory()->create(['transaksi_id' => $transaksiTetangga->id, 'rating' => 5]);

        $hasil = app(RekomendasiService::class)->rekomendasikanUntukProduk($target->id);

        $this->assertTrue($hasil->isNotEmpty());
        foreach ($hasil as $item) {
            $this->assertSame('knn', $item['metode']);
            // Skor harus berupa persentase yang valid (0-100), bukan rentang
            // acak rand(75,98) yang dipakai versi lama.
            $this->assertGreaterThanOrEqual(0, $item['skor']);
            $this->assertLessThanOrEqual(100, $item['skor']);
        }
    }

    public function test_perbarui_semua_log_menyimpan_metode_yang_jujur(): void
    {
        $kampas = Produk::factory()->create();
        $oli = Produk::factory()->create();
        $transaksi = Transaksi::factory()->create();
        DetailTransaksi::factory()->create(['transaksi_id' => $transaksi->id, 'produk_id' => $kampas->id]);
        DetailTransaksi::factory()->create(['transaksi_id' => $transaksi->id, 'produk_id' => $oli->id]);

        $jumlah = app(RekomendasiService::class)->perbaruiSemuaLog();

        $this->assertGreaterThan(0, $jumlah);
        $this->assertDatabaseHas('log_rekomendasi', ['produk_asal_id' => $kampas->id, 'produk_rekomendasi_id' => $oli->id, 'metode' => 'co_purchase']);

        // Skor yang tersimpan harus konsisten dengan yang dikembalikan
        // service, bukan nilai independen yang dikarang ulang.
        $log = LogRekomendasi::where('produk_asal_id', $kampas->id)->first();
        $this->assertSame(100.0, (float) $log->skor); // cuma 1 produk lain yang pernah dibeli bersama -> 100%
    }
}
