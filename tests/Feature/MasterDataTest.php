<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_menambah_siswa_dengan_password_ter_hash_dan_role_siswa(): void
    {
        $this->actingAs($this->buatAdmin())->post(route('admin.siswa.store'), [
            'name' => 'Siswa Baru', 'email' => 'baru@uji.test', 'nis_nip' => '99001', 'kelas' => 'XII RPL 1',
            'password' => 'Aman12345',
            'role' => 'admin', // upaya menaikkan hak akses lewat manipulasi form
            'aktif' => 0,
        ])->assertSessionHasNoErrors();

        $siswa = User::where('email', 'baru@uji.test')->firstOrFail();
        $this->assertSame('siswa', $siswa->role);
        $this->assertTrue($siswa->aktif);
        $this->assertNotSame('Aman12345', $siswa->password);
        $this->assertTrue(Hash::check('Aman12345', $siswa->password));
    }

    public function test_nis_dan_email_harus_unik_dan_password_harus_kuat(): void
    {
        $this->buatSiswa(['email' => 'dobel@uji.test', 'nis_nip' => '777']);
        $this->actingAs($this->buatAdmin());

        $this->post(route('admin.siswa.store'), ['name' => 'A', 'email' => 'dobel@uji.test', 'nis_nip' => '778', 'password' => 'Aman12345'])
            ->assertSessionHasErrors('email');
        $this->post(route('admin.siswa.store'), ['name' => 'B', 'email' => 'lain@uji.test', 'nis_nip' => '777', 'password' => 'Aman12345'])
            ->assertSessionHasErrors('nis_nip');
        $this->post(route('admin.siswa.store'), ['name' => 'C', 'email' => 'c@uji.test', 'password' => 'pendek'])
            ->assertSessionHasErrors('password');
        $this->post(route('admin.siswa.store'), ['name' => 'D', 'email' => 'd@uji.test', 'password' => 'hurufsajaaa'])
            ->assertSessionHasErrors('password');
    }

    public function test_admin_mengubah_dan_menonaktifkan_siswa_tanpa_mengganti_password(): void
    {
        $siswa = $this->buatSiswa();
        $hashLama = $siswa->password;

        $this->actingAs($this->buatAdmin())->put(route('admin.siswa.update', $siswa), [
            'name' => 'Nama Baru', 'email' => $siswa->email, 'nis_nip' => $siswa->nis_nip, 'aktif' => 0,
        ])->assertSessionHasNoErrors();

        $siswa->refresh();
        $this->assertSame('Nama Baru', $siswa->name);
        $this->assertFalse($siswa->aktif);
        $this->assertSame($hashLama, $siswa->password);
    }

    public function test_siswa_yang_punya_riwayat_tidak_bisa_dihapus(): void
    {
        $siswa = $this->buatSiswa();
        Pengajuan::factory()->for($siswa, 'user')->create();

        $this->actingAs($this->buatAdmin())->delete(route('admin.siswa.destroy', $siswa))->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $siswa->id]);
        $this->assertSame(1, Pengajuan::count());
    }

    public function test_siswa_tanpa_riwayat_bisa_dihapus(): void
    {
        $siswa = $this->buatSiswa();

        $this->actingAs($this->buatAdmin())->delete(route('admin.siswa.destroy', $siswa))->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['id' => $siswa->id]);
    }

    public function test_akun_admin_tidak_bisa_diubah_lewat_menu_siswa(): void
    {
        $adminLain = $this->buatAdmin();

        $this->actingAs($this->buatAdmin())->get(route('admin.siswa.edit', $adminLain))->assertNotFound();
        $this->delete(route('admin.siswa.destroy', $adminLain))->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $adminLain->id]);
    }

    public function test_jenis_dokumen_kode_otomatis_kapital_dan_unik(): void
    {
        $this->actingAs($this->buatAdmin());

        $this->post(route('admin.dokumen.store'), ['kode' => 'abc', 'nama' => 'Dokumen ABC', 'layanan' => ['legalisir']])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('document_types', ['kode' => 'ABC', 'aktif' => 1]);

        $this->post(route('admin.dokumen.store'), ['kode' => 'AbC', 'nama' => 'Lain', 'layanan' => ['legalisir']])->assertSessionHasErrors('kode');
    }

    public function test_admin_dapat_menyimpan_jenis_dokumen_tanpa_pemetaan_layanan(): void
    {
        $dokumen = DocumentType::factory()->create(['kode' => 'SKL', 'layanan' => ['legalisir']]);

        $this->actingAs($this->buatAdmin())->put(route('admin.dokumen.update', $dokumen), [
            'kode' => 'SKL',
            'nama' => 'Surat Keterangan Lulus',
            'aktif' => 1,
            // Tidak ada checkbox layanan yang dipilih.
        ])->assertSessionHasNoErrors();

        $this->assertSame([], $dokumen->fresh()->layanan);
    }

    public function test_jenis_dokumen_yang_sudah_dipakai_tidak_bisa_dihapus_tetapi_bisa_dinonaktifkan(): void
    {
        $dokumen = DocumentType::factory()->create();
        Pengajuan::factory()->for($this->buatSiswa(), 'user')->create(['document_type_id' => $dokumen->id]);
        $this->actingAs($this->buatAdmin());

        $this->delete(route('admin.dokumen.destroy', $dokumen))->assertSessionHas('error');
        $this->assertDatabaseHas('document_types', ['id' => $dokumen->id]);

        $this->put(route('admin.dokumen.update', $dokumen), ['kode' => $dokumen->kode, 'nama' => $dokumen->nama, 'aktif' => 0, 'layanan' => ['legalisir']])
            ->assertSessionHasNoErrors();
        $this->assertFalse($dokumen->fresh()->aktif);
    }

    public function test_jenis_dokumen_yang_belum_dipakai_bisa_dihapus(): void
    {
        $dokumen = DocumentType::factory()->create();

        $this->actingAs($this->buatAdmin())->delete(route('admin.dokumen.destroy', $dokumen))->assertSessionHas('status');

        $this->assertDatabaseMissing('document_types', ['id' => $dokumen->id]);
    }

    public function test_pencarian_siswa_meloloskan_karakter_wildcard_dengan_aman(): void
    {
        $this->buatSiswa(['name' => 'Budi Santoso']);
        $this->buatSiswa(['name' => 'Citra Dewi']);

        // "%" tidak boleh dianggap pola yang cocok dengan semua nama.
        $this->actingAs($this->buatAdmin())->get(route('admin.siswa.index', ['q' => '%']))
            ->assertOk()->assertDontSee('Budi Santoso')->assertDontSee('Citra Dewi');
        $this->get(route('admin.siswa.index', ['q' => 'budi']))->assertOk()->assertSee('Budi Santoso')->assertDontSee('Citra Dewi');
    }

    public function test_filter_dan_paginasi_pengajuan_admin(): void
    {
        $siswa = $this->buatSiswa(['name' => 'Pencari Uji']);
        Pengajuan::factory()->for($siswa, 'user')->count(12)->create();
        Pengajuan::factory()->for($this->buatSiswa(), 'user')->status('selesai')->create();
        $this->actingAs($this->buatAdmin());

        $this->get(route('admin.pengajuan.index'))->assertOk()
            ->assertViewHas('pengajuans', fn ($p) => $p->total() === 13 && $p->perPage() === 10);
        $this->get(route('admin.pengajuan.index', ['status' => 'selesai']))
            ->assertViewHas('pengajuans', fn ($p) => $p->total() === 1);
        $this->get(route('admin.pengajuan.index', ['q' => 'Pencari Uji']))
            ->assertViewHas('pengajuans', fn ($p) => $p->total() === 12);
        $this->get(route('admin.pengajuan.index', ['status' => 'bukan-status']))
            ->assertViewHas('pengajuans', fn ($p) => $p->total() === 13); // filter tak valid diabaikan
    }
}
