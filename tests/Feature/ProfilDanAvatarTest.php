<?php

namespace Tests\Feature;

use App\Models\Pengajuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilDanAvatarTest extends TestCase
{
    use RefreshDatabase;

    private function ubahProfil($user, array $tambahan = [])
    {
        return $this->actingAs($user)->put(route('profile.update'), array_merge([
            'name' => $user->name, 'email' => $user->email,
        ], $tambahan));
    }

    public function test_foto_profil_disimpan_privat_dan_tidak_bisa_diakses_lewat_url_publik(): void
    {
        Storage::fake('berkas');
        Storage::fake('public');
        $user = $this->buatSiswa();

        $this->ubahProfil($user, ['foto' => UploadedFile::fake()->create('saya.jpg', 100, 'image/jpeg')])
            ->assertSessionHasNoErrors();

        $foto = $user->fresh()->foto;
        $this->assertNotNull($foto);
        Storage::disk('berkas')->assertExists($foto);
        Storage::disk('public')->assertMissing($foto);
    }

    public function test_hanya_pemilik_dan_admin_yang_boleh_melihat_foto(): void
    {
        Storage::fake('berkas');
        Storage::disk('berkas')->put('foto-profil/a.jpg', 'JPEGDATA');
        $pemilik = $this->buatSiswa(['foto' => 'foto-profil/a.jpg']);

        $respons = $this->actingAs($pemilik)->get(route('avatar.show', $pemilik));
        $respons->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', $respons->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('no-store', $respons->headers->get('Cache-Control'));

        $this->actingAs($this->buatAdmin())->get(route('avatar.show', $pemilik))->assertOk();
        // Siswa lain: 404 (foto siswa tidak boleh bisa diintip dengan mengganti ID)
        $this->actingAs($this->buatSiswa())->get(route('avatar.show', $pemilik))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->get(route('avatar.show', $pemilik))->assertRedirect('/login');
    }

    public function test_pengguna_tanpa_foto_menghasilkan_404_dan_halaman_memakai_inisial(): void
    {
        $user = $this->buatSiswa(['name' => 'Rina Tanpa Foto']);

        $this->actingAs($user)->get(route('avatar.show', $user))->assertNotFound();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('RT')->assertDontSee('/avatar/');
    }

    public function test_halaman_profil_menautkan_foto_lewat_route_berotorisasi(): void
    {
        Storage::fake('berkas');
        Storage::disk('berkas')->put('foto-profil/b.png', 'PNGDATA');
        $user = $this->buatSiswa(['foto' => 'foto-profil/b.png']);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('/avatar/' . $user->id . '?v=', false);
    }

    public function test_mengganti_foto_menghapus_foto_lama(): void
    {
        Storage::fake('berkas');
        Storage::disk('berkas')->put('foto-profil/lama.jpg', 'LAMA');
        $user = $this->buatSiswa(['foto' => 'foto-profil/lama.jpg']);

        $this->ubahProfil($user, ['foto' => UploadedFile::fake()->create('baru.png', 50, 'image/png')])->assertSessionHasNoErrors();

        Storage::disk('berkas')->assertMissing('foto-profil/lama.jpg');
        Storage::disk('berkas')->assertExists($user->fresh()->foto);
    }

    /** @return array<string, array{0:UploadedFile}> */
    public static function fotoTidakValid(): array
    {
        return [
            'svg (dapat memuat skrip)' => [UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml')],
            'gif' => [UploadedFile::fake()->create('x.gif', 5, 'image/gif')],
            'php' => [UploadedFile::fake()->create('x.php', 5, 'text/x-php')],
            'pdf' => [UploadedFile::fake()->create('x.pdf', 5, 'application/pdf')],
            'terlalu besar' => [UploadedFile::fake()->create('besar.jpg', 3000, 'image/jpeg')],
        ];
    }

    /**
     * @dataProvider fotoTidakValid
     */
    public function test_foto_tidak_valid_ditolak(UploadedFile $berkas): void
    {
        Storage::fake('berkas');
        $user = $this->buatSiswa();

        $this->ubahProfil($user, ['foto' => $berkas])->assertSessionHasErrors('foto');

        $this->assertNull($user->fresh()->foto);
        $this->assertSame([], Storage::disk('berkas')->allFiles());
    }

    public function test_menghapus_siswa_ikut_menghapus_fotonya(): void
    {
        Storage::fake('berkas');
        Storage::disk('berkas')->put('foto-profil/c.jpg', 'X');
        $siswa = $this->buatSiswa(['foto' => 'foto-profil/c.jpg']);

        $this->actingAs($this->buatAdmin())->delete(route('admin.siswa.destroy', $siswa))->assertSessionHas('status');

        Storage::disk('berkas')->assertMissing('foto-profil/c.jpg');
    }

    public function test_pengguna_tidak_bisa_mengubah_nis_kelas_atau_role_lewat_profil(): void
    {
        $siswa = $this->buatSiswa(['nis_nip' => '1111', 'kelas' => 'XII A']);

        $this->ubahProfil($siswa, ['nis_nip' => '9999', 'kelas' => 'XII Z', 'role' => 'admin', 'aktif' => 0])->assertSessionHasNoErrors();

        $siswa->refresh();
        $this->assertSame('1111', $siswa->nis_nip);
        $this->assertSame('XII A', $siswa->kelas);
        $this->assertSame('siswa', $siswa->role);
        $this->assertTrue($siswa->aktif);
    }

    public function test_email_baru_harus_unik(): void
    {
        $lain = $this->buatSiswa(['email' => 'dipakai@uji.test']);
        $user = $this->buatSiswa();

        $this->ubahProfil($user, ['email' => 'dipakai@uji.test'])->assertSessionHasErrors('email');
        $this->ubahProfil($user, ['email' => $user->email])->assertSessionHasNoErrors(); // email sendiri boleh
    }

    public function test_ganti_password_mewajibkan_password_lama_dan_aturan_kuat(): void
    {
        $user = $this->buatSiswa();
        $this->actingAs($user);

        $this->put(route('profile.password'), ['current_password' => 'salah', 'password' => 'BaruAman123', 'password_confirmation' => 'BaruAman123'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('profile.password'), ['current_password' => 'Rahasia123', 'password' => 'lemah', 'password_confirmation' => 'lemah'])
            ->assertSessionHasErrors('password');
        $this->put(route('profile.password'), ['current_password' => 'Rahasia123', 'password' => 'BaruAman123', 'password_confirmation' => 'beda'])
            ->assertSessionHasErrors('password');
        $this->put(route('profile.password'), ['current_password' => 'Rahasia123', 'password' => 'Rahasia123', 'password_confirmation' => 'Rahasia123'])
            ->assertSessionHasErrors('password'); // harus berbeda dari yang lama

        $this->put(route('profile.password'), ['current_password' => 'Rahasia123', 'password' => 'BaruAman123', 'password_confirmation' => 'BaruAman123'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('BaruAman123', $user->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'update_password']);
    }

    public function test_halaman_profil_dan_notifikasi_tampil_untuk_kedua_role(): void
    {
        foreach ([$this->buatSiswa(), $this->buatAdmin()] as $user) {
            Pengajuan::factory()->for($user, 'user')->create();
            $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee($user->email);
            $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Belum ada notifikasi');
        }
    }
}
