<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_utama_menampilkan_daftar_produk(): void
    {
        $produk = Produk::factory()->create(['nama' => 'Kampas Rem Depan']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Kampas Rem Depan');
    }

    public function test_filter_kategori_hanya_menampilkan_produk_pada_kategori_itu(): void
    {
        $mesin = Kategori::factory()->create(['nama' => 'Mesin']);
        $kelistrikan = Kategori::factory()->create(['nama' => 'Kelistrikan']);
        Produk::factory()->create(['nama' => 'Oli Mesin 1L', 'kategori_id' => $mesin->id]);
        Produk::factory()->create(['nama' => 'Busi Iridium', 'kategori_id' => $kelistrikan->id]);

        $response = $this->get('/?kategori='.$mesin->id);

        $response->assertOk();
        $response->assertSee('Oli Mesin 1L');
        $response->assertDontSee('Busi Iridium');
    }

    public function test_produk_dengan_stok_habis_tidak_bisa_ditambah_ke_keranjang(): void
    {
        $produk = Produk::factory()->create(['nama' => 'Aki Kering', 'stok' => 0]);

        $response = $this->post(route('keranjang.tambah', $produk));

        $response->assertSessionHas('galat');
        $response->assertSessionMissing('sukses');
    }

    public function test_pencarian_barcode_menambahkan_produk_yang_cocok_ke_keranjang(): void
    {
        $produk = Produk::factory()->create(['barcode' => '8991234567890', 'stok' => 5]);

        $response = $this->post(route('keranjang.scan'), ['barcode' => '8991234567890']);

        $response->assertSessionHas('sukses');
    }

    public function test_pencarian_barcode_yang_tidak_ada_menampilkan_pesan_galat(): void
    {
        $response = $this->post(route('keranjang.scan'), ['barcode' => 'tidak-ada-begini']);

        $response->assertSessionHas('galat');
    }
}
