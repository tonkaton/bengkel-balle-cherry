<?php

namespace Tests\Feature\Admin;

use App\Models\DetailTransaksi;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdukCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create();
    }

    public function test_menambah_produk_wajib_menyertakan_kategori(): void
    {
        // Regresi untuk temuan F14: form tambah produk versi lama tidak
        // punya pilihan kategori sama sekali, jadi kategori_id selalu NULL.
        $kategori = Kategori::factory()->create();

        $response = $this->actingAs($this->admin())->post(route('admin.produk.store'), [
            'barcode' => '1234567890123',
            'nama' => 'Kampas Rem Belakang',
            'harga' => 75000,
            'stok' => 10,
            // kategori_id sengaja tidak disertakan
        ]);

        $response->assertSessionHasErrors('kategori_id');
        $this->assertDatabaseCount('produk', 0);

        $response = $this->actingAs($this->admin())->post(route('admin.produk.store'), [
            'barcode' => '1234567890123',
            'nama' => 'Kampas Rem Belakang',
            'harga' => 75000,
            'stok' => 10,
            'kategori_id' => $kategori->id,
        ]);

        $response->assertRedirect(route('admin.produk.index'));
        $this->assertDatabaseHas('produk', ['nama' => 'Kampas Rem Belakang', 'kategori_id' => $kategori->id]);
    }

    public function test_barcode_duplikat_ditolak(): void
    {
        Produk::factory()->create(['barcode' => '1111111111111']);
        $kategori = Kategori::factory()->create();

        $response = $this->actingAs($this->admin())->post(route('admin.produk.store'), [
            'barcode' => '1111111111111',
            'nama' => 'Produk Lain',
            'harga' => 10000,
            'stok' => 1,
            'kategori_id' => $kategori->id,
        ]);

        $response->assertSessionHasErrors('barcode');
    }

    public function test_produk_yang_sudah_terjual_tidak_bisa_dihapus(): void
    {
        $produk = Produk::factory()->create();
        DetailTransaksi::factory()->create(['produk_id' => $produk->id]);

        $response = $this->actingAs($this->admin())->delete(route('admin.produk.destroy', $produk));

        $response->assertSessionHas('galat');
        $this->assertDatabaseHas('produk', ['id' => $produk->id]);
    }

    public function test_produk_yang_belum_pernah_terjual_bisa_dihapus(): void
    {
        $produk = Produk::factory()->create();

        $response = $this->actingAs($this->admin())->delete(route('admin.produk.destroy', $produk));

        $response->assertSessionHas('sukses');
        $this->assertDatabaseMissing('produk', ['id' => $produk->id]);
    }

    public function test_pengunjung_tanpa_login_tidak_bisa_mengakses_crud_produk(): void
    {
        $this->get(route('admin.produk.index'))->assertRedirect('/login');
        $this->get(route('admin.produk.create'))->assertRedirect('/login');
    }
}
