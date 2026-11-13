<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TotpAuthenticator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_totp_memverifikasi_kode_dengan_jendela_waktu(): void
    {
        $totp = app(TotpAuthenticator::class);
        $secret = $totp->generateSecret();
        $timestamp = 1_700_000_000;
        $code = $totp->currentCode($secret, $timestamp);

        $this->assertTrue($totp->verify($secret, $code, $timestamp));
        $this->assertFalse($totp->verify($secret, '000000', $timestamp + 360));
    }

    public function test_admin_dengan_2fa_harus_menyelesaikan_tantangan_sebelum_login(): void
    {
        $totp = app(TotpAuthenticator::class);
        $secret = $totp->generateSecret();
        $admin = $this->buatAdmin(['email' => 'admin.2fa@example.sch.id']);
        $admin->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => Crypt::encryptString('[]'),
        ])->save();

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'Rahasia123'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->get(route('two-factor.challenge'))->assertOk()->assertSee('Verifikasi keamanan');

        $code = $totp->currentCode($secret);
        $this->post(route('two-factor.challenge.store'), ['code' => $code])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_recovery_code_hanya_dapat_digunakan_satu_kali(): void
    {
        $admin = $this->buatAdmin(['email' => 'admin.recovery@example.sch.id']);
        $secret = app(TotpAuthenticator::class)->generateSecret();
        $recovery = 'ABCD-EFGH-IJKL';
        $admin->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode([Hash::make($recovery)])),
        ])->save();

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'Rahasia123'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->post(route('two-factor.challenge.store'), ['code' => $recovery])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
        $remaining = json_decode(Crypt::decryptString($admin->fresh()->two_factor_recovery_codes), true);
        $this->assertSame([], $remaining);
    }

    public function test_admin_dapat_mengaktifkan_2fa_dan_menerima_recovery_code_sekali(): void
    {
        $admin = $this->buatAdmin();
        $this->actingAs($admin)->post(route('admin.security.two-factor.begin'))->assertRedirect();
        $encrypted = session('two_factor_setup_secret');
        $this->assertNotEmpty($encrypted);
        $secret = Crypt::decryptString($encrypted);
        $code = app(TotpAuthenticator::class)->currentCode($secret);

        $this->post(route('admin.security.two-factor.confirm'), ['code' => $code])
            ->assertRedirect(route('admin.security.two-factor'))
            ->assertSessionHas('recovery_codes');

        $this->assertTrue($admin->fresh()->twoFactorEnabled());
        $this->assertNotEmpty($admin->fresh()->two_factor_recovery_codes);
    }

    public function test_siswa_tidak_dapat_membuka_pengaturan_2fa_admin(): void
    {
        $this->actingAs($this->buatSiswa())->get(route('admin.security.two-factor'))->assertForbidden();
    }
}
