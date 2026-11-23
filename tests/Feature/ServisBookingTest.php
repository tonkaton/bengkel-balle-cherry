<?php

namespace Tests\Feature;

use App\Models\BookingServis;
use App\Models\Produk;
use App\Models\Servis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServisBookingTest extends TestCase
{
    use RefreshDatabase;

    private function dataBooking(array $ubah = []): array
    {
        return array_merge([
            'nama_pelanggan' => 'Budi',
            'no_hp' => '081234567890',
            'kendaraan' => 'Honda Vario 125',
            'plat_nomor' => 'B 1234 ABC',
            'tanggal' => now()->addDay()->toDateString(),
            'jam' => '09:00',
            'keluhan' => 'Rem depan bunyi',
        ], $ubah);
    }

    public function test_halaman_servis_dan_form_booking_tampil_terpisah_dari_katalog(): void
    {
        $servis = Servis::factory()->create(['nama' => 'Servis Ringan']);

        $this->get(route('servis.index'))->assertOk()->assertSee('Servis Ringan');
        $this->get(route('servis.booking', $servis))->assertOk()->assertSee('Kirim Booking');
        $this->get(route('home'))->assertOk()->assertDontSee('Servis Ringan');
    }

    public function test_pelanggan_bisa_booking_dan_melihat_status_dengan_kode_booking(): void
    {
        $servis = Servis::factory()->create();

        $respon = $this->post(route('servis.booking.simpan', $servis), $this->dataBooking());

        $booking = BookingServis::firstOrFail();
        $respon->assertRedirect(route('booking.show', $booking));
        $this->assertSame(BookingServis::MENUNGGU, $booking->status);
        $this->assertStringStartsWith('BKG', $booking->kode_booking);
        $this->get(route('booking.show', $booking))->assertOk()->assertSee($booking->kode_booking)->assertSee('Menunggu konfirmasi');
    }

    public function test_booking_menolak_tanggal_lampau_jam_di_luar_layanan_dan_nomor_hp_tidak_valid(): void
    {
        $servis = Servis::factory()->create();

        $this->post(route('servis.booking.simpan', $servis), $this->dataBooking([
            'tanggal' => now()->subDay()->toDateString(),
            'jam' => '22:00',
            'no_hp' => '12345',
        ]))->assertSessionHasErrors(['tanggal', 'jam', 'no_hp']);

        $this->assertSame(0, BookingServis::count());
    }

    public function test_booking_hari_ini_menolak_jam_yang_sudah_lewat_dan_status_menyamarkan_nomor_hp(): void
    {
        $this->travelTo(now()->setTime(12, 30));
        $servis = Servis::factory()->create();
        $hariIni = now()->toDateString();

        $this->post(route('servis.booking.simpan', $servis), $this->dataBooking(['tanggal' => $hariIni, 'jam' => '09:00']))
            ->assertSessionHasErrors('jam');
        $this->post(route('servis.booking.simpan', $servis), $this->dataBooking(['tanggal' => $hariIni, 'jam' => '14:00']))
            ->assertSessionHasNoErrors();

        $booking = BookingServis::firstOrFail();
        $this->get(route('booking.show', $booking))->assertSee('0812******90')->assertDontSee('081234567890');
    }

    public function test_admin_menyelesaikan_booking_menjadi_transaksi_servis_dan_sparepart(): void
    {
        $admin = User::factory()->create();
        $servis = Servis::factory()->create(['harga' => 100000]);
        $oli = Produk::factory()->create(['harga' => 65000, 'stok' => 10]);
        $booking = BookingServis::factory()->create(['servis_id' => $servis->id]);

        $this->actingAs($admin)->patch(route('admin.booking.konfirmasi', $booking))->assertRedirect();
        $respon = $this->actingAs($admin)->post(route('admin.booking.selesaikan', $booking), ['sparepart' => [$oli->id => 2]]);

        $booking->refresh();
        $transaksi = $booking->transaksi;
        $respon->assertRedirect(route('struk.show', $transaksi));
        $this->assertSame(BookingServis::SELESAI, $booking->status);
        $this->assertSame(100000 + 2 * 65000, $transaksi->total_harga);
        $this->assertDatabaseHas('detail_transaksi', ['transaksi_id' => $transaksi->id, 'servis_id' => $servis->id, 'produk_id' => null, 'jumlah' => 1]);
        $this->assertDatabaseHas('detail_transaksi', ['transaksi_id' => $transaksi->id, 'produk_id' => $oli->id, 'jumlah' => 2]);
        $this->assertSame(8, $oli->fresh()->stok);

        // Struk menampilkan nama servis dan link rating, jadi servis ikut masuk data KNN setelah dirating.
        $this->get(route('struk.show', $transaksi))->assertOk()->assertSee($servis->nama)->assertSee($transaksi->rating_token);
    }

    public function test_selesaikan_booking_dibatalkan_seluruhnya_kalau_stok_sparepart_kurang(): void
    {
        $admin = User::factory()->create();
        $oli = Produk::factory()->create(['stok' => 1]);
        $booking = BookingServis::factory()->create();

        $this->actingAs($admin)->post(route('admin.booking.selesaikan', $booking), ['sparepart' => [$oli->id => 5]])
            ->assertSessionHas('galat');

        $this->assertSame(BookingServis::MENUNGGU, $booking->fresh()->status);
        $this->assertSame(0, \App\Models\Transaksi::count());
        $this->assertSame(1, $oli->fresh()->stok);
    }

    public function test_booking_yang_sudah_selesai_atau_batal_tidak_bisa_diproses_lagi(): void
    {
        $admin = User::factory()->create();
        $booking = BookingServis::factory()->create(['status' => BookingServis::BATAL]);

        $this->actingAs($admin)->post(route('admin.booking.selesaikan', $booking))->assertSessionHas('galat');
        $this->actingAs($admin)->patch(route('admin.booking.konfirmasi', $booking))->assertSessionHas('galat');
        $this->assertSame(BookingServis::BATAL, $booking->fresh()->status);
    }

    public function test_admin_bisa_menambah_servis_dan_servis_yang_sudah_dibooking_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.servis.store'), [
            'nama' => 'Ganti Kampas Rem', 'deskripsi' => 'Jasa pasang kampas', 'harga' => 30000, 'estimasi_menit' => 30,
        ])->assertRedirect(route('admin.servis.index'));
        $this->assertDatabaseHas('servis', ['nama' => 'Ganti Kampas Rem', 'harga' => 30000]);

        $booking = BookingServis::factory()->create();
        $this->actingAs($admin)->delete(route('admin.servis.destroy', $booking->servis))->assertSessionHas('galat');
        $this->assertModelExists($booking->servis);
    }
}
