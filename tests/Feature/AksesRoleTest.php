<?php

namespace Tests\Feature;

use App\Models\Pengajuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AksesRoleTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0:string}> */
    public static function rutePerAdmin(): array
    {
        return [
            'data siswa' => ['/admin/siswa'],
            'tambah siswa' => ['/admin/siswa/create'],
            'jenis dokumen' => ['/admin/dokumen'],
            'pengajuan masuk' => ['/admin/pengajuan'],
            'scan qr' => ['/admin/scan-qr'],
            'laporan' => ['/admin/laporan'],
            'cetak laporan' => ['/admin/laporan/cetak'],
            'ekspor excel' => ['/admin/laporan/export/excel'],
            'log aktivitas' => ['/admin/aktivitas'],
            'kelola panduan' => ['/admin/panduan'],
            'tambah panduan' => ['/admin/panduan/create'],
        ];
    }

    /**
     * @dataProvider rutePerAdmin
     */
    public function test_siswa_tidak_bisa_membuka_halaman_admin(string $url): void
    {
        $this->actingAs($this->buatSiswa())->get($url)
            ->assertForbidden()
            ->assertSee('Akses ditolak');
    }

    /**
     * @dataProvider rutePerAdmin
     */
    public function test_admin_bisa_membuka_halaman_admin(string $url): void
    {
        $respons = $this->actingAs($this->buatAdmin())->get($url);

        $this->assertTrue(in_array($respons->getStatusCode(), [200], true), "{$url} => " . $respons->getStatusCode());
    }

    public function test_admin_tidak_bisa_memakai_layanan_milik_siswa(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->get('/pengajuan')->assertForbidden();
        $this->actingAs($admin)->get('/pengajuan/buat')->assertForbidden();
        $this->actingAs($admin)->post('/pengajuan', ['jenis_layanan' => 'peminjaman_rapor', 'keperluan' => 'x'])->assertForbidden();
    }

    public function test_siswa_tidak_bisa_mengubah_status_dengan_memanggil_endpoint_admin(): void
    {
        $siswa = $this->buatSiswa();
        $pengajuan = Pengajuan::factory()->for($siswa, 'user')->create();

        // Menyembunyikan menu saja tidak cukup: server harus menolak.
        $this->actingAs($siswa)->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'diproses'])->assertForbidden();
        $this->actingAs($siswa)->post(route('admin.pengajuan.qrKonfirmasi', $pengajuan))->assertForbidden();

        $this->assertSame('pending', $pengajuan->fresh()->status);
    }

    public function test_dashboard_menyesuaikan_role(): void
    {
        $this->actingAs($this->buatAdmin(['name' => 'Petugas Uji']))->get('/dashboard')
            ->assertOk()->assertSee('Antrean verifikasi')->assertSee('Petugas');

        $this->actingAs($this->buatSiswa(['name' => 'Siswa Uji']))->get('/dashboard')
            ->assertOk()->assertSee('Ajukan layanan baru')->assertDontSee('Antrean verifikasi');
    }

    public function test_dashboard_admin_memakai_data_nyata_bukan_statis(): void
    {
        $siswa = $this->buatSiswa();
        Pengajuan::factory()->for($siswa, 'user')->count(3)->create();
        Pengajuan::factory()->for($siswa, 'user')->status('selesai')->create();

        $this->actingAs($this->buatAdmin())->get('/dashboard')
            ->assertOk()
            ->assertViewHas('ringkas', fn ($r) => $r['total'] === 4 && $r['pending'] === 3 && $r['selesai'] === 1 && $r['siswa'] === 1)
            ->assertViewHas('bulanan', fn ($b) => count($b) === 6 && end($b)['total'] === 4);
    }

    public function test_siswa_hanya_melihat_data_miliknya_di_dashboard(): void
    {
        $a = $this->buatSiswa();
        $b = $this->buatSiswa();
        Pengajuan::factory()->for($a, 'user')->count(2)->create();
        Pengajuan::factory()->for($b, 'user')->count(5)->create();

        $this->actingAs($a)->get('/dashboard')
            ->assertViewHas('ringkasan', fn ($r) => $r['total'] === 2);
    }

    public function test_halaman_error_404_memakai_desain_eksidu(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('EKSiDU');
    }
}
