<?php

namespace Tests\Feature\Admin;

use App\Models\DetailTransaksi;
use App\Models\Produk;
use App\Models\RatingKomen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_semua_halaman_admin_bisa_diakses_setelah_login(): void
    {
        $admin = User::factory()->create();

        $halaman = [
            route('admin.dashboard'),
            route('admin.produk.index'),
            route('admin.produk.create'),
            route('admin.kategori.index'),
            route('admin.rating.index'),
            route('admin.analisis-stok.index'),
            route('admin.rekomendasi.index'),
            route('admin.profile.edit'),
        ];

        foreach ($halaman as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_dashboard_tidak_menampilkan_klaim_akurasi_yang_tidak_pernah_dihitung(): void
    {
        // Regresi untuk temuan F06: versi lama menampilkan "Akurasi: 85-95%"
        // sebagai teks statis di beberapa halaman. Dashboard versi ini
        // sama sekali tidak boleh menampilkan klaim akurasi semacam itu.
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('85-95%');
        $response->assertDontSee('Akurasi');
    }

    public function test_analisis_stok_tidak_lagi_mengklaim_dirinya_algoritma_knn(): void
    {
        // Regresi untuk temuan F08: halaman ini dulu bernama "Analisis Stok
        // dengan KNN" padahal isinya aturan ambang batas biasa.
        $admin = User::factory()->create();
        $produk = Produk::factory()->create();
        DetailTransaksi::factory()->create(['produk_id' => $produk->id, 'jumlah' => 60]);

        $response = $this->actingAs($admin)->get(route('admin.analisis-stok.index'));

        $response->assertOk();
        $response->assertSee('Analisis Popularitas Produk');
        $response->assertSee('Sangat Laris');
        $response->assertDontSee('Analisis Stok dengan KNN');
    }

    public function test_admin_bisa_menerapkan_rekomendasi_stok(): void
    {
        $admin = User::factory()->create();
        $produk = Produk::factory()->create(['stok' => 10]);
        DetailTransaksi::factory()->create(['produk_id' => $produk->id, 'jumlah' => 55]); // >= 50 => Sangat Laris => stok x3

        $response = $this->actingAs($admin)->post(route('admin.analisis-stok.update', $produk));

        $response->assertSessionHas('sukses');
        $produk->refresh();
        $this->assertSame(30, $produk->stok);
    }

    public function test_admin_bisa_menghapus_rating_pelanggan(): void
    {
        $admin = User::factory()->create();
        $rating = RatingKomen::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.rating.destroy', $rating));

        $response->assertSessionHas('sukses');
        $this->assertDatabaseMissing('rating_komen', ['id' => $rating->id]);
    }

    public function test_export_csv_menghasilkan_berkas_yang_bisa_diunduh(): void
    {
        $admin = User::factory()->create();
        Produk::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.export.produk'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_pengunjung_tanpa_login_tidak_bisa_mengekspor_laporan(): void
    {
        $this->get(route('admin.export.produk'))->assertRedirect('/login');
    }
}
