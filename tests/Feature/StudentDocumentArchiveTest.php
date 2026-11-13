<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Pengajuan;
use App\Models\StudentDocumentArchive;
use App\Models\StudentDocumentArchiveHistory;
use App\Models\StudentArchiveRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StudentDocumentArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function archive($student, array $overrides = []): StudentDocumentArchive
    {
        $identity = array_merge([
            'category' => 'rapor', 'academic_year' => '2026/2027', 'semester' => 1,
            'report_period' => 'akhir', 'publication_year' => null,
        ], $overrides);
        return StudentDocumentArchive::create(array_merge($identity, [
            'user_id' => $student->id,
            'identity_key' => hash('sha256', json_encode([$student->id, $identity['category'], $identity['academic_year'] ?? '', $identity['semester'] ?? '', $identity['report_period'] ?? '', $identity['publication_year'] ?? ''])),
            'digital_status' => 'belum_diunggah', 'physical_status' => 'tersedia', 'version' => 1,
        ], $overrides));
    }


    public function test_route_persyaratan_tidak_ditangkap_parameter_siswa(): void
    {
        $uri = parse_url(route('admin.arsip-siswa.requirements.store'), PHP_URL_PATH);
        $matched = app('router')->getRoutes()->match(Request::create($uri, 'POST'));

        $this->assertSame('admin.arsip-siswa.requirements.store', $matched->getName());
    }

    public function test_aturan_kelengkapan_duplikat_ditolak(): void
    {
        $admin = $this->buatAdmin();
        StudentArchiveRequirement::create([
            'class_name' => 'XII RPL 1', 'academic_year' => '2026/2027', 'category' => 'rapor',
            'semester' => 1, 'report_period' => 'akhir', 'required' => true, 'active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.arsip-siswa.requirements.store'), [
            'class_name' => 'XII RPL 1', 'academic_year' => '2026/2027', 'category' => 'rapor',
            'semester' => 1, 'report_period' => 'akhir', 'required' => 1,
        ])->assertSessionHasErrors('category');

        $this->assertDatabaseCount('student_archive_requirements', 1);
    }

    public function test_kategori_non_rapor_tidak_menerima_metadata_semester_rapor(): void
    {
        $admin = $this->buatAdmin();
        $student = $this->buatSiswa();

        $this->actingAs($admin)->post(route('admin.arsip-siswa.store', $student), [
            'category' => 'tka', 'academic_year' => '2026/2027', 'semester' => 1,
            'report_period' => 'akhir', 'publication_year' => 2026,
            'digital_status' => 'belum_diunggah', 'physical_status' => 'tersedia',
        ])->assertSessionHasErrors('academic_year');

        $this->assertDatabaseCount('student_document_archives', 0);
    }

    public function test_admin_dapat_membuat_arsip_fisik_per_siswa_dan_riwayat_status(): void
    {
        $admin = $this->buatAdmin();
        $student = $this->buatSiswa();
        $this->actingAs($admin)->post(route('admin.arsip-siswa.store', $student), [
            'category' => 'rapor', 'academic_year' => '2026/2027', 'semester' => 1,
            'report_period' => 'akhir', 'digital_status' => 'belum_diunggah',
            'physical_status' => 'tersedia', 'physical_location' => 'Lemari TU A-1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $archive = StudentDocumentArchive::firstOrFail();
        $this->assertSame($student->id, $archive->user_id);
        $this->actingAs($admin)->patch(route('admin.arsip-siswa.status', $archive), [
            'digital_status' => 'belum_diunggah', 'physical_status' => 'hilang',
            'physical_location' => 'Pencarian inventaris', 'notes' => 'Dokumen belum ditemukan',
        ])->assertSessionHasNoErrors();
        $this->assertSame('hilang', $archive->fresh()->physical_status);
        $this->assertDatabaseHas('student_document_archive_histories', [
            'archive_id' => $archive->id, 'field' => 'physical_status', 'old_value' => 'tersedia', 'new_value' => 'hilang',
        ]);
    }

    public function test_arsip_fisik_yang_hilang_membatalkan_pengajuan_disetujui_dan_kode(): void
    {
        $admin = $this->buatAdmin();
        $student = $this->buatSiswa();
        $archive = $this->archive($student);
        $code = 'EKSIDU-TESTCODE123456';
        $pengajuan = Pengajuan::factory()->for($student, 'user')->status('approved')->create([
            'archive_id' => $archive->id, 'bentuk_dokumen' => 'fisik', 'kategori_layanan' => 'rapor',
            'jenis_layanan' => 'peminjaman_rapor', 'physical_verification_hash' => hash('sha256', $code),
            'physical_verification_code' => Crypt::encryptString($code), 'physical_verification_expires_at' => now()->addDays(3),
        ]);
        $this->actingAs($admin)->patch(route('admin.arsip-siswa.status', $archive), [
            'digital_status' => 'belum_diunggah', 'physical_status' => 'hilang', 'notes' => 'Hasil pengecekan TU',
        ])->assertSessionHasNoErrors();
        $saved = $pengajuan->fresh();
        $this->assertSame('rejected', $saved->status);
        $this->assertNull($saved->physical_verification_hash);
        $this->assertNull($saved->physical_verification_code);
        $this->assertDatabaseHas('pengajuan_riwayats', ['pengajuan_id' => $pengajuan->id, 'status_ke' => 'rejected']);
    }

    public function test_status_digital_tidak_boleh_tersedia_tanpa_file(): void
    {
        $admin = $this->buatAdmin();
        $student = $this->buatSiswa();
        $this->actingAs($admin)->post(route('admin.arsip-siswa.store', $student), [
            'category' => 'ijazah', 'publication_year' => 2026,
            'digital_status' => 'tersedia', 'physical_status' => 'belum_tercatat',
        ])->assertSessionHasErrors('digital_status');
        $this->assertDatabaseCount('student_document_archives', 0);
    }

    public function test_qr_digital_dapat_dibuka_berulang_dan_hanya_oleh_pemilik(): void
    {
        Storage::fake('berkas');
        $student = $this->buatSiswa();
        $other = $this->buatSiswa();
        $document = Document::factory()->create(['user_id' => $student->id, 'disk' => 'berkas', 'file_path' => 'arsip-siswa/rapor.pdf', 'status' => 'available']);
        Storage::disk('berkas')->put('arsip-siswa/rapor.pdf', '%PDF-1.4 sample');
        $archive = $this->archive($student, ['document_id' => $document->id, 'digital_status' => 'tersedia']);
        $pengajuan = Pengajuan::factory()->for($student, 'user')->status('approved')->create([
            'archive_id' => $archive->id, 'document_version_id' => $document->id,
            'bentuk_dokumen' => 'digital', 'kategori_layanan' => 'rapor', 'jenis_layanan' => 'peminjaman_rapor',
        ]);
        $token = 'token-digital-berulang-untuk-pengujian-123456';
        $pengajuan->update(['digital_access_hash' => hash('sha256', $token), 'digital_access_token' => Crypt::encryptString($token)]);
        $url = URL::temporarySignedRoute('siswa.dokumen.digital', now()->addDay(), ['pengajuan' => $pengajuan->id, 'token' => $token], false);

        $this->actingAs($student)->get($url)->assertOk()->assertSee('QR dapat digunakan berulang kali');
        $this->actingAs($student)->get($url)->assertOk();
        $this->actingAs($other)->get($url)->assertForbidden();
    }

    public function test_hasil_legalisir_digital_disimpan_terpisah_dan_menjadi_snapshot_akses(): void
    {
        Storage::fake('berkas');
        $student = $this->buatSiswa();
        $admin = $this->buatAdmin();
        $source = Document::factory()->create(['user_id' => $student->id, 'disk' => 'berkas', 'file_path' => 'arsip-siswa/asli.pdf', 'status' => 'available']);
        Storage::disk('berkas')->put('arsip-siswa/asli.pdf', '%PDF-1.4 original');
        $archive = $this->archive($student, ['document_id' => $source->id, 'digital_status' => 'tersedia']);
        $pengajuan = Pengajuan::factory()->for($student, 'user')->status('diproses')->create([
            'archive_id' => $archive->id, 'document_version_id' => $source->id,
            'bentuk_dokumen' => 'digital', 'kategori_layanan' => 'rapor', 'jenis_layanan' => 'legalisir',
        ]);

        $this->actingAs($admin)->post(route('admin.pengajuan.legalisir-result', $pengajuan), [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('hasil-legalisir.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $saved = $pengajuan->fresh();
        $this->assertNotNull($saved->result_document_id);
        $this->assertNotSame($source->id, $saved->result_document_id);
        $this->assertSame($saved->result_document_id, $saved->document_version_id);
        $this->assertSame('arsip-siswa/asli.pdf', $source->fresh()->file_path);

        $this->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertNotNull($pengajuan->fresh()->digital_access_hash);
    }

    public function test_qr_digital_revoke_dan_reissue_membatalkan_token_lama(): void
    {
        Storage::fake('berkas');
        $student = $this->buatSiswa();
        $admin = $this->buatAdmin();
        $document = Document::factory()->create(['user_id' => $student->id, 'disk' => 'berkas', 'file_path' => 'arsip-siswa/revoke.pdf', 'status' => 'available']);
        Storage::disk('berkas')->put('arsip-siswa/revoke.pdf', '%PDF-1.4 data');
        $archive = $this->archive($student, ['document_id' => $document->id, 'digital_status' => 'tersedia']);
        $token = 'token-digital-revoke-test-1234567890';
        $pengajuan = Pengajuan::factory()->for($student, 'user')->status('approved')->create([
            'archive_id' => $archive->id, 'document_version_id' => $document->id, 'bentuk_dokumen' => 'digital',
            'kategori_layanan' => 'rapor', 'jenis_layanan' => 'peminjaman_rapor',
            'digital_access_hash' => hash('sha256', $token), 'digital_access_token' => Crypt::encryptString($token),
        ]);
        $oldUrl = URL::temporarySignedRoute('siswa.dokumen.digital', now()->addDay(), ['pengajuan' => $pengajuan->id, 'token' => $token], false);
        $this->actingAs($student)->get($oldUrl)->assertOk();
        $this->actingAs($admin)->post(route('admin.pengajuan.digital-revoke', $pengajuan))->assertSessionHasNoErrors();
        $this->actingAs($student)->get($oldUrl)->assertNotFound();
        $this->actingAs($admin)->post(route('admin.pengajuan.digital-reissue', $pengajuan))->assertSessionHasNoErrors();
        $newToken = Crypt::decryptString($pengajuan->fresh()->digital_access_token);
        $newUrl = URL::temporarySignedRoute('siswa.dokumen.digital', now()->addDay(), ['pengajuan' => $pengajuan->id, 'token' => $newToken], false);
        $this->actingAs($student)->get($oldUrl)->assertNotFound();
        $this->get($newUrl)->assertOk();
    }

    public function test_rotasi_kode_fisik_membatalkan_qr_dan_kode_lama(): void
    {
        $student = $this->buatSiswa();
        $admin = $this->buatAdmin();
        $archive = $this->archive($student);
        $oldCode = 'EKSIDU-OLDPHYSICALCODE1234';
        $pengajuan = Pengajuan::factory()->for($student, 'user')->status('approved')->create([
            'archive_id' => $archive->id, 'bentuk_dokumen' => 'fisik', 'kategori_layanan' => 'rapor',
            'jenis_layanan' => 'peminjaman_rapor', 'physical_verification_hash' => hash('sha256', $oldCode),
            'physical_verification_code' => Crypt::encryptString($oldCode), 'physical_verification_expires_at' => now()->addDays(5),
        ]);
        $oldQr = URL::temporarySignedRoute('pengajuan.qr.show', now()->addDays(5), ['pengajuan' => $pengajuan->id, 'v' => substr($pengajuan->physical_verification_hash, 0, 16)], false);
        $this->actingAs($admin)->post(route('admin.pengajuan.physical-code-rotate', $pengajuan))->assertSessionHasNoErrors();
        $this->actingAs($admin)->get($oldQr)->assertNotFound();
        $newCode = Crypt::decryptString($pengajuan->fresh()->physical_verification_code);
        $this->assertNotSame($oldCode, $newCode);
        $this->post(route('admin.scan-qr.cari'), ['kode_tiket' => $oldCode])->assertSessionHasErrors('kode_tiket');
    }

    public function test_persetujuan_fisik_menerbitkan_kode_verifikasi_acak_terhash(): void
    {
        $student = $this->buatSiswa();
        $admin = $this->buatAdmin();
        $archive = $this->archive($student);
        $pengajuan = Pengajuan::factory()->for($student, 'user')->create([
            'archive_id' => $archive->id, 'bentuk_dokumen' => 'fisik', 'kategori_layanan' => 'rapor',
            'jenis_layanan' => 'peminjaman_rapor',
        ]);
        $this->actingAs($admin)->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'diproses'])->assertSessionHasNoErrors();
        $this->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'approved'])->assertSessionHasNoErrors();
        $saved = $pengajuan->fresh();
        $plain = Crypt::decryptString($saved->physical_verification_code);
        $this->assertMatchesRegularExpression('/^EKSIDU-[A-Z0-9]{16}$/', $plain);
        $this->assertSame(hash('sha256', $plain), $saved->physical_verification_hash);
        $this->assertNotNull($saved->physical_verification_expires_at);
    }
}
