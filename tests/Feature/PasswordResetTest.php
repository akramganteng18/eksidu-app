<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_dapat_membuka_form_lupa_kata_sandi(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Lupa kata sandi?');
    }

    public function test_tautan_reset_dikirim_melalui_broker_laravel(): void
    {
        Notification::fake();
        $user = $this->buatSiswa(['email' => 'reset@example.sch.id']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()->assertSessionHas('status');

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
    }

    public function test_token_valid_dapat_mengganti_password_dan_token_tidak_dapat_dipakai_lagi(): void
    {
        $user = $this->buatSiswa(['email' => 'reset2@example.sch.id']);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('PasswordBaru123', $user->fresh()->password));

        $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'PasswordLain123',
                'password_confirmation' => 'PasswordLain123',
            ])->assertSessionHasErrors('email');
    }
    public function test_akun_nonaktif_tidak_menerima_notifikasi_reset(): void
    {
        Notification::fake();
        $user = $this->buatSiswa(['email' => 'nonaktif@example.sch.id', 'aktif' => false]);

        $response = $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()->assertSessionHas('status');

        Notification::assertNothingSent();
        $this->assertStringContainsString('Jika alamat email terdaftar', session('status'));
    }

}
