<?php

namespace Tests\Feature;

use App\Models\Pengumuman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengumumanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_membuat_pengumuman_dan_pengumuman_siswa_tampil_di_dashboard_siswa(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->post(route('admin.pengumuman.store'), [
            'judul' => 'Jadwal pengambilan ijazah',
            'isi' => 'Pengambilan dilayani pada jam kerja TU.',
            'sasaran' => 'siswa',
            'diterbitkan' => '1',
        ])->assertRedirect(route('admin.pengumuman.index'))->assertSessionHasNoErrors();

        $pengumuman = Pengumuman::firstOrFail();
        $this->assertTrue($pengumuman->diterbitkan);
        $this->actingAs($this->buatSiswa())->get(route('dashboard'))->assertOk()->assertSee('Jadwal pengambilan ijazah');
    }

    public function test_pengumuman_admin_tidak_ditampilkan_kepada_siswa(): void
    {
        Pengumuman::create([
            'judul' => 'Informasi internal TU',
            'isi' => 'Informasi khusus petugas.',
            'sasaran' => 'admin',
            'diterbitkan' => true,
        ]);

        $this->actingAs($this->buatSiswa())->get(route('dashboard'))->assertOk()->assertDontSee('Informasi internal TU');
    }

    public function test_pengumuman_draft_dan_kedaluwarsa_tidak_tampil(): void
    {
        Pengumuman::create(['judul' => 'Draft', 'isi' => 'Belum tayang.', 'sasaran' => 'semua', 'diterbitkan' => false]);
        Pengumuman::create(['judul' => 'Sudah berakhir', 'isi' => 'Tidak boleh tampil.', 'sasaran' => 'semua', 'diterbitkan' => true, 'berakhir_pada' => now()->subMinute()]);

        $this->actingAs($this->buatAdmin())->get(route('dashboard'))->assertOk()
            ->assertDontSee('Draft')->assertDontSee('Sudah berakhir');
    }

    public function test_siswa_tidak_dapat_mengelola_pengumuman(): void
    {
        $this->actingAs($this->buatSiswa())->get(route('admin.pengumuman.index'))->assertForbidden();
    }
}
