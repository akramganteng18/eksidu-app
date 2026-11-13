<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiswaImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_melihat_pratinjau_dan_mengimpor_csv_siswa(): void
    {
        $admin = $this->buatAdmin();
        $csv = "name,email,nis_nip,kelas,no_telp,password\n".
            "Siswa Impor,siswa.impor@example.sch.id,009988,XII RPL 1,08123456789,PasswordKuat123\n";

        $response = $this->actingAs($admin)->post(route('admin.siswa.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('siswa.csv', $csv),
        ])->assertRedirect(route('admin.siswa.import.create'))->assertSessionHasNoErrors();

        $batch = session('siswa_import_preview.batch');
        $this->assertNotEmpty($batch);
        $this->assertStringNotContainsString('PasswordKuat123', json_encode(session('siswa_import_preview')));

        $this->post(route('admin.siswa.import.commit'), ['batch' => $batch])->assertRedirect(route('admin.siswa.index'));
        $siswa = User::where('email', 'siswa.impor@example.sch.id')->firstOrFail();
        $this->assertSame('siswa', $siswa->role);
        $this->assertTrue(Hash::check('PasswordKuat123', $siswa->password));
    }

    public function test_csv_dengan_email_duplikat_ditolak_dan_tidak_membuat_akun(): void
    {
        $this->buatAdmin();
        $csv = "name,email,nis_nip,kelas,no_telp,password\n".
            "Satu,duplikat@example.sch.id,001,XII RPL 1,,PasswordKuat123\n".
            "Dua,duplikat@example.sch.id,002,XII RPL 1,,PasswordKuat123\n";

        $this->actingAs(User::where('role', 'admin')->first())->post(route('admin.siswa.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('duplikat.csv', $csv),
        ])->assertRedirect()->assertSessionHas('import_errors');

        $this->assertDatabaseMissing('users', ['email' => 'duplikat@example.sch.id']);
    }

    public function test_siswa_tidak_dapat_mengakses_form_impor(): void
    {
        $this->actingAs($this->buatSiswa())->get(route('admin.siswa.import.create'))->assertForbidden();
    }
}
