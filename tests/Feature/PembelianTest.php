<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji alur belanja lengkap: tambah ke keranjang -> checkout -> struk ->
 * beri rating. Ini jalur paling kritis di seluruh aplikasi karena
 * menyangkut uang dan stok, jadi diuji paling detail.
 */
class PembelianTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_membuat_transaksi_dan_mengurangi_stok(): void
    {
        $produk = Produk::factory()->create(['harga' => 85000, 'stok' => 10]);

        $this->post(route('keranjang.tambah', $produk));
        $response = $this->post(route('checkout.store'));

        $produk->refresh();
        $this->assertSame(9, $produk->stok);
        $this->assertDatabaseHas('transaksi', ['total_harga' => 85000]);
        $this->assertDatabaseHas('detail_transaksi', ['produk_id' => $produk->id, 'jumlah' => 1, 'harga_saat_beli' => 85000]);

        $transaksi = Transaksi::first();
        $response->assertRedirect(route('struk.show', $transaksi));
    }

    public function test_checkout_dengan_keranjang_kosong_ditolak(): void
    {
        $response = $this->post(route('checkout.store'));

        $response->assertSessionHas('galat');
        $this->assertDatabaseCount('transaksi', 0);
    }

    public function test_checkout_menolak_jika_stok_tidak_mencukupi(): void
    {
        $produk = Produk::factory()->create(['stok' => 2]);

        // Coba tambahkan 3x padahal stok cuma 2.
        $this->post(route('keranjang.tambah', $produk));
        $this->post(route('keranjang.tambah', $produk));
        $this->post(route('keranjang.ubah', $produk), ['jumlah' => 5]);

        $response = $this->post(route('checkout.store'));

        $response->assertSessionHas('galat');
        $this->assertDatabaseCount('transaksi', 0);
        $produk->refresh();
        $this->assertSame(2, $produk->stok, 'Stok tidak boleh berubah kalau checkout gagal.');
    }

    public function test_harga_yang_tersimpan_selalu_berasal_dari_database_bukan_dari_klien(): void
    {
        // Regresi untuk celah manipulasi harga yang ditemukan di luar
        // Laporan Audit Teknis: versi PHP lama mempercayai begitu saja
        // angka "harga" yang dikirim body JSON checkout. Di sini, keranjang
        // cuma menyimpan produk_id + jumlah di session (lihat CartService),
        // jadi tidak ada cara bagi "klien" untuk mengirim harga sama sekali
        // - checkout SELALU membaca ulang Produk::harga dari database.
        $produk = Produk::factory()->create(['harga' => 999999]);

        $this->post(route('keranjang.tambah', $produk));
        $this->post(route('checkout.store'));

        $this->assertDatabaseHas('detail_transaksi', ['produk_id' => $produk->id, 'harga_saat_beli' => 999999]);
    }

    public function test_checkout_mengosongkan_keranjang_dan_menghasilkan_rating_token_unik(): void
    {
        $produk = Produk::factory()->create();
        $this->post(route('keranjang.tambah', $produk));
        $this->post(route('checkout.store'));

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Keranjang masih kosong');

        $transaksi = Transaksi::first();
        $this->assertNotEmpty($transaksi->rating_token);
        $this->assertSame(48, strlen($transaksi->rating_token));
    }

    public function test_pelanggan_bisa_memberi_rating_lewat_token_yang_valid(): void
    {
        $transaksi = Transaksi::factory()->create();

        $response = $this->post(route('rating.store', $transaksi->rating_token), [
            'rating' => 5,
            'komen' => 'Pelayanan cepat dan ramah.',
        ]);

        $response->assertRedirect(route('struk.show', $transaksi));
        $this->assertDatabaseHas('rating_komen', ['transaksi_id' => $transaksi->id, 'rating' => 5]);
    }

    public function test_token_rating_yang_salah_menghasilkan_404_bukan_membocorkan_transaksi_orang_lain(): void
    {
        // Regresi untuk temuan F11: transaksi_id yang mudah ditebak dulu
        // bisa dipakai siapa saja untuk mengirim rating atas nama orang
        // lain. Sekarang, token acak yang salah harus gagal total (404),
        // bukan malah "berhasil menebak" transaksi milik orang lain.
        Transaksi::factory()->create();

        $response = $this->post('/rating/token-acak-yang-salah-dan-tidak-terdaftar', [
            'rating' => 5,
        ]);

        $response->assertNotFound();
    }

    public function test_transaksi_yang_sama_tidak_bisa_dirating_dua_kali(): void
    {
        $transaksi = Transaksi::factory()->create();
        $this->post(route('rating.store', $transaksi->rating_token), ['rating' => 4]);

        $response = $this->post(route('rating.store', $transaksi->rating_token), ['rating' => 1]);

        $response->assertRedirect(route('struk.show', $transaksi));
        $this->assertDatabaseCount('rating_komen', 1);
        $this->assertDatabaseHas('rating_komen', ['rating' => 4]);
    }

    public function test_struk_menggunakan_kode_transaksi_bukan_id_mentah_di_url(): void
    {
        // Regresi untuk temuan F03: struk.php lama menerima parameter id
        // mentah tanpa validasi. Sekarang route model binding lewat
        // kode_transaksi menangani pencarian & 404 secara otomatis.
        $transaksi = Transaksi::factory()->create();

        $response = $this->get('/struk/'.$transaksi->kode_transaksi);
        $response->assertOk();

        $responseTidakAda = $this->get('/struk/kode-yang-tidak-pernah-ada');
        $responseTidakAda->assertNotFound();
    }
}
