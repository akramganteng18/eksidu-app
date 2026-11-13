<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_dialihkan_ke_login_saat_membuka_halaman_privat(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/pengajuan')->assertRedirect('/login');
        $this->get('/admin/siswa')->assertRedirect('/login');
    }

    public function test_halaman_login_tampil_dengan_identitas_eksidu(): void
    {
        $this->get('/login')->assertOk()->assertSee('EKSiDU')->assertDontSee('SIMADA');
    }

    public function test_beranda_mengarahkan_sesuai_status_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->actingAs($this->buatSiswa())->get('/')->assertRedirect('/dashboard');
    }

    public function test_login_berhasil_dan_sesi_diregenerasi(): void
    {
        $admin = $this->buatAdmin(['email' => 'admin@uji.test']);

        $this->post('/login', ['email' => 'admin@uji.test', 'password' => 'Rahasia123'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'login']);
    }

    public function test_login_gagal_tidak_membuka_sesi_dan_tidak_membocorkan_info(): void
    {
        $this->buatSiswa(['email' => 'ada@uji.test']);

        // Kasus 1: akun ada, kata sandi salah.
        $this->from('/login')->post('/login', ['email' => 'ada@uji.test', 'password' => 'salah-total'])
            ->assertSessionHasErrors('email');
        $pesanAkunAda = session('errors')->first('email');

        // Kasus 2: akun tidak ada sama sekali.
        $this->from('/login')->post('/login', ['email' => 'tidakada@uji.test', 'password' => 'salah-total'])
            ->assertSessionHasErrors('email');
        $pesanAkunTidakAda = session('errors')->first('email');

        $this->assertGuest();
        // Pesan identik: halaman login tidak bisa dipakai untuk menebak akun yang terdaftar.
        $this->assertSame($pesanAkunAda, $pesanAkunTidakAda);
        $this->assertStringContainsString('salah', $pesanAkunAda);
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        User::factory()->nonaktif()->create(['email' => 'off@uji.test']);

        $this->post('/login', ['email' => 'off@uji.test', 'password' => 'Rahasia123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('dinonaktifkan', session('errors')->first('email'));
    }

    public function test_login_dikunci_setelah_lima_kegagalan(): void
    {
        $this->buatSiswa(['email' => 'kunci@uji.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'kunci@uji.test', 'password' => 'salah-' . $i]);
        }

        // Percobaan ke-6, bahkan dengan password BENAR, ditolak karena terkunci.
        $this->post('/login', ['email' => 'kunci@uji.test', 'password' => 'Rahasia123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('email'));
    }

    public function test_logout_mengakhiri_sesi_dan_halaman_privat_tertutup_lagi(): void
    {
        $user = $this->buatSiswa();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_halaman_privat_melarang_cache_browser(): void
    {
        $respons = $this->actingAs($this->buatSiswa())->get('/dashboard');

        $respons->assertOk();
        $this->assertStringContainsString('no-store', $respons->headers->get('Cache-Control'));
    }

    public function test_pengguna_yang_sudah_login_tidak_melihat_halaman_login_lagi(): void
    {
        // Regresi: dulu diarahkan ke "/home" yang tidak ada (404).
        $this->actingAs($this->buatSiswa())->get('/login')->assertRedirect('/dashboard');
    }

    public function test_sesi_akun_yang_dinonaktifkan_admin_langsung_diputus(): void
    {
        $user = User::factory()->nonaktif()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_registrasi_publik_tertutup_secara_bawaan(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Penyusup', 'email' => 'x@x.test', 'nis_nip' => '1', 'kelas' => 'X',
            'password' => 'Abcdef123', 'password_confirmation' => 'Abcdef123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'x@x.test']);
    }

    public function test_header_keamanan_terpasang(): void
    {
        $respons = $this->get('/login');

        $respons->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respons->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
