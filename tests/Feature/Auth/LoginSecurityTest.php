<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tes regresi khusus untuk temuan F01 pada Laporan Audit Teknis: SQL
 * Injection pada formulir login admin versi PHP lama.
 *
 * Di versi lama, mengirim username seperti admin' --  berhasil melewati
 * validasi kata sandi sepenuhnya karena kueri SQL dibangun lewat
 * penggabungan string mentah. Tes ini memastikan payload yang SAMA PERSIS
 * tidak lagi bisa dipakai untuk membobol sistem versi Laravel ini, karena
 * autentikasi sekarang ditangani lewat Eloquent + Hash::check() (bcrypt)
 * bawaan Laravel yang otomatis memakai prepared statement.
 */
class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sql_injection_payload_tidak_bisa_membobol_login(): void
    {
        User::factory()->create(['email' => 'admin@ballechery.test', 'password' => 'admin123']);

        $response = $this->post('/login', [
            'email' => "admin' -- ",
            'password' => 'apa-saja-asal-bukan-yang-benar',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_login_gagal_dengan_kata_sandi_yang_salah(): void
    {
        User::factory()->create(['email' => 'admin@ballechery.test', 'password' => 'admin123']);

        $response = $this->post('/login', [
            'email' => 'admin@ballechery.test',
            'password' => 'salah-total',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_login_berhasil_dengan_kredensial_yang_benar(): void
    {
        $user = User::factory()->create(['email' => 'admin@ballechery.test', 'password' => 'admin123']);

        $response = $this->post('/login', [
            'email' => 'admin@ballechery.test',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_tersimpan_dalam_bentuk_hash_bcrypt_bukan_md5(): void
    {
        // Perbaikan untuk temuan F04: kata sandi tidak boleh lagi tersimpan
        // sebagai MD5('admin123') apa adanya.
        $user = User::factory()->create(['password' => 'admin123']);

        $this->assertNotEquals(md5('admin123'), $user->password);
        $this->assertTrue(str_starts_with($user->password, '$2y$'));
    }

    public function test_halaman_pendaftaran_akun_baru_tidak_tersedia(): void
    {
        // Aplikasi ini cuma punya satu admin (dibuat lewat seeder), jadi
        // rute /register sengaja tidak didaftarkan sama sekali.
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_rute_admin_menolak_pengunjung_yang_belum_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }
}
