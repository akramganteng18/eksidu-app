<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\Pengajuan;
use App\Models\PengajuanRiwayat;
use App\Models\StudentDocumentArchive;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    private function jalankan(string $kelas): void
    {
        // Dipanggil langsung (bukan db:seed) agar tidak terhalang konfirmasi "produksi" pada simulasi lingkungan.
        (new $kelas())->setContainer(app())->run();
    }

    public function test_seeder_dasar_aman_dijalankan_berulang(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(4, DocumentType::count());
    }

    public function test_seeder_dasar_tidak_membuat_data_demo_bila_tidak_diaktifkan(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::where('role', 'siswa')->count());
        $this->assertSame(0, Pengajuan::count());
    }

    public function test_password_admin_tidak_disimpan_sebagai_teks_biasa(): void
    {
        $this->seed(AdminSeeder::class);

        $admin = User::where('role', 'admin')->firstOrFail();
        $this->assertNotSame(config('eksidu.demo_password'), $admin->password);
        $this->assertTrue(Hash::check(config('eksidu.demo_password'), $admin->password));
        $this->assertStringStartsWith('$2y$', $admin->password);
    }

    public function test_menjalankan_ulang_seeder_tidak_menimpa_password_atau_perubahan_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', 'admin')->firstOrFail();
        $admin->update(['password' => Hash::make('SudahDiganti99')]);
        DocumentType::where('kode', 'RPR')->update(['nama' => 'Nama Hasil Edit Admin', 'aktif' => false]);

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('SudahDiganti99', $admin->fresh()->password));
        $rapor = DocumentType::where('kode', 'RPR')->firstOrFail();
        $this->assertSame('Nama Hasil Edit Admin', $rapor->nama);
        $this->assertFalse($rapor->aktif);
    }

    public function test_di_luar_lokal_tanpa_admin_password_dibuat_kata_sandi_acak_bukan_password_bawaan(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['eksidu.admin.password' => null]);

        $this->jalankan(AdminSeeder::class);

        $admin = User::where('role', 'admin')->firstOrFail();
        $this->assertFalse(Hash::check('password', $admin->password));
        $this->assertFalse(Hash::check(config('eksidu.demo_password'), $admin->password));
    }

    public function test_admin_password_dari_env_dipakai_bila_diisi(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['eksidu.admin.password' => 'KataSandiKuat#2026']);

        $this->jalankan(AdminSeeder::class);

        $this->assertTrue(Hash::check('KataSandiKuat#2026', User::where('role', 'admin')->firstOrFail()->password));
    }

    public function test_seeder_demo_idempoten_dan_konsisten(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(DocumentTypeSeeder::class);

        $this->seed(DemoSeeder::class);
        $siswa = User::where('role', 'siswa')->count();
        $pengajuan = Pengajuan::count();
        $riwayat = PengajuanRiwayat::count();
        $arsip = StudentDocumentArchive::count();

        $this->seed(DemoSeeder::class);

        $this->assertSame(5, $siswa);
        $this->assertSame($siswa, User::where('role', 'siswa')->count());
        $this->assertSame($pengajuan, Pengajuan::count());
        $this->assertSame($riwayat, PengajuanRiwayat::count());
        $this->assertSame(15, $arsip);
        $this->assertSame($arsip, StudentDocumentArchive::count());
        $this->assertSame(10, StudentDocumentArchive::where('category', 'rapor')->count());
        $this->assertSame(5, StudentDocumentArchive::whereIn('category', ['tka', 'skls', 'ijazah'])->count());
        $this->assertSame($pengajuan, Pengajuan::distinct()->count('kode_tiket'), 'kode tiket harus unik');
        $this->assertGreaterThan(0, $pengajuan);
    }

    public function test_data_demo_memakai_kata_sandi_ter_hash_dan_status_beragam(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(DemoSeeder::class);

        $siswa = User::where('email', 'ahmad.fauzan@siswa.smkn5surakarta.sch.id')->firstOrFail();
        $this->assertNotSame(config('eksidu.demo_password'), $siswa->password);
        $this->assertTrue(Hash::check(config('eksidu.demo_password'), $siswa->password));
        $this->assertGreaterThanOrEqual(4, Pengajuan::distinct()->count('status'));
    }

    public function test_seeder_demo_ditolak_di_produksi(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['eksidu.seed_demo_production' => false]);

        $this->expectException(\RuntimeException::class);
        $this->jalankan(DemoSeeder::class);
    }

    public function test_seeder_demo_di_produksi_hanya_berjalan_dengan_opt_in_dan_password_kuat(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config([
            'eksidu.seed_demo_production' => true,
            'eksidu.akun_demo' => false,
            'eksidu.demo_password' => 'PasswordPameran#2026',
        ]);

        $this->jalankan(DatabaseSeeder::class);

        $siswa = User::where('role', 'siswa')->count();
        $this->assertSame(5, $siswa);
        $this->assertGreaterThan(0, Pengajuan::count());
        $this->assertTrue(Hash::check(
            'PasswordPameran#2026',
            User::where('email', 'ahmad.fauzan@siswa.smkn5surakarta.sch.id')->firstOrFail()->password
        ));
    }

    public function test_seeder_demo_di_produksi_menolak_password_yang_terlalu_pendek(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config([
            'eksidu.seed_demo_production' => true,
            'eksidu.demo_password' => 'password',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('minimal 12 karakter');

        $this->jalankan(DemoSeeder::class);
    }
}
