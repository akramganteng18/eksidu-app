<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentLoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DocumentRepositoryTest extends TestCase
{
    use RefreshDatabase;














    public function test_menu_repository_siswa_diarahkan_ke_dokumen_saya(): void
    {
        $this->actingAs($this->buatSiswa())->get(route('siswa.repository.index'))
            ->assertRedirect(route('siswa.dokumen-saya.index'));
    }

    public function test_repository_lama_menolak_pengajuan_dan_upload_baru(): void
    {
        $siswa = $this->buatSiswa();
        $admin = $this->buatAdmin();
        $document = Document::factory()->create();
        $this->actingAs($siswa)->post(route('siswa.repository.request', $document), ['purpose' => 'Keperluan sekolah'])
            ->assertRedirect(route('siswa.pengajuan.create'));
        $this->assertDatabaseCount('document_loans', 0);
        $this->actingAs($admin)->post(route('admin.repository.store'), [
            'title' => 'Dokumen baru', 'file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('admin.arsip-siswa.index'));
        $this->assertDatabaseCount('documents', 1);
    }

    public function test_siswa_tidak_dapat_mengakses_repository_admin(): void
    {
        $this->actingAs($this->buatSiswa())->get(route('admin.repository.index'))->assertForbidden();
    }

    public function test_metadata_repository_lama_tidak_dapat_diubah_dan_file_asli_dipertahankan(): void
    {
        Storage::fake('berkas');
        $admin = $this->buatAdmin();
        $document = Document::factory()->create(['disk' => 'berkas', 'file_path' => 'repository/keep.pdf']);
        Storage::disk('berkas')->put('repository/keep.pdf', 'file lama');
        $this->actingAs($admin)->patch(route('admin.repository.update', $document), ['title' => 'Diubah'])
            ->assertRedirect(route('admin.arsip-siswa.index'));
        $this->assertSame('repository/keep.pdf', $document->fresh()->file_path);
        Storage::disk('berkas')->assertExists('repository/keep.pdf');
    }

    public function test_qr_repository_dapat_dibuka_peminjam_dan_mengarah_ke_unduhan(): void
    {
        $siswa = $this->buatSiswa();
        $document = Document::factory()->create(['title' => 'Ijazah Digital', 'status' => 'available']);
        $token = 'token-acak-untuk-pengujian-akses-qr';
        $loan = DocumentLoan::create([
            'document_id' => $document->id,
            'user_id' => $siswa->id,
            'purpose' => 'Keperluan administrasi sekolah',
            'status' => 'approved',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(3),
        ]);
        $url = URL::temporarySignedRoute('repository.qr.access', now()->addDays(3), [
            'loan' => $loan->id, 'token' => $token,
        ], false);

        $this->actingAs($siswa)->get($url)->assertOk()
            ->assertSee('Ijazah Digital')
            ->assertSee(route('siswa.repository.download', $loan))
            ->assertSee(route('repository.preview', $loan));

        $this->assertNotNull($loan->fresh()->accessed_at);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $siswa->id,
            'action' => 'repository_loan_qr_access',
        ]);
    }

    public function test_peminjam_dapat_melihat_berkas_repository_secara_inline(): void
    {
        Storage::fake('berkas');
        Storage::disk('berkas')->put('repository/pratinjau.pdf', '%PDF-1.4 dokumen uji');
        $siswa = $this->buatSiswa();
        $document = Document::factory()->create([
            'disk' => 'berkas', 'file_path' => 'repository/pratinjau.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 20, 'status' => 'available',
        ]);
        $loan = DocumentLoan::create([
            'document_id' => $document->id, 'user_id' => $siswa->id,
            'purpose' => 'Membaca dokumen sekolah', 'status' => 'approved',
            'token_hash' => hash('sha256', 'token-preview-uji'), 'expires_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($siswa)->get(route('repository.preview', $loan));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertNotNull($loan->fresh()->accessed_at);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $siswa->id, 'action' => 'repository_loan_preview',
        ]);
    }

    public function test_siswa_lain_tidak_dapat_melihat_pratinjau_dokumen_pinjamannya(): void
    {
        $pemilik = $this->buatSiswa();
        $document = Document::factory()->create(['status' => 'available']);
        $loan = DocumentLoan::create([
            'document_id' => $document->id, 'user_id' => $pemilik->id,
            'purpose' => 'Keperluan administrasi sekolah', 'status' => 'approved',
            'token_hash' => hash('sha256', 'token-pratinjau-rahasia'), 'expires_at' => now()->addDays(2),
        ]);

        $this->actingAs($this->buatSiswa())->get(route('repository.preview', $loan))->assertNotFound();
    }

    public function test_qr_repository_tidak_dapat_dibuka_siswa_lain(): void
    {
        $pemilik = $this->buatSiswa();
        $siswaLain = $this->buatSiswa();
        $document = Document::factory()->create(['status' => 'available']);
        $token = 'token-rahasia-pemilik-saja';
        $loan = DocumentLoan::create([
            'document_id' => $document->id,
            'user_id' => $pemilik->id,
            'purpose' => 'Keperluan administrasi sekolah',
            'status' => 'approved',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(3),
        ]);
        $url = URL::temporarySignedRoute('repository.qr.access', now()->addDays(3), [
            'loan' => $loan->id, 'token' => $token,
        ], false);

        $this->actingAs($siswaLain)->get($url)->assertNotFound();
    }

    public function test_admin_dapat_memperbarui_qr_yang_masih_aktif_setelah_alamat_aplikasi_berubah(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatSiswa();
        $document = Document::factory()->create(['status' => 'available']);
        $tokenLama = 'token-lama-aktif-yang-sudah-terbit';
        $loan = DocumentLoan::create([
            'document_id' => $document->id, 'user_id' => $siswa->id,
            'approved_by' => $admin->id, 'purpose' => 'Administrasi sekolah',
            'status' => 'approved', 'token_hash' => hash('sha256', $tokenLama),
            'qr_svg' => '<svg>qr-lama</svg>', 'expires_at' => now()->addDays(5),
        ]);
        $oldUrl = URL::temporarySignedRoute('repository.qr.access', now()->addDays(5), [
            'loan' => $loan->id, 'token' => $tokenLama,
        ], false);

        $this->actingAs($admin)->patch(route('admin.repository.loans.update', $loan), [
            'status' => 'approved',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertNotSame(hash('sha256', $tokenLama), $loan->token_hash);
        $this->assertNotSame('<svg>qr-lama</svg>', $loan->qr_svg);
        $this->assertTrue($loan->expires_at->isFuture());
        $this->assertNull($loan->revoked_at);
        $this->actingAs($siswa)->get($oldUrl)->assertNotFound();
    }

    public function test_admin_dapat_menerbitkan_ulang_qr_yang_kedaluwarsa(): void
    {
        $admin = $this->buatAdmin();
        $siswa = $this->buatSiswa();
        $document = Document::factory()->create(['status' => 'available']);
        $loan = DocumentLoan::create([
            'document_id' => $document->id,
            'user_id' => $siswa->id,
            'approved_by' => $admin->id,
            'purpose' => 'Keperluan administrasi sekolah',
            'status' => 'approved',
            'token_hash' => hash('sha256', 'token-lama-yang-sudah-kedaluwarsa'),
            'qr_svg' => '<svg></svg>',
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)->patch(route('admin.repository.loans.update', $loan), [
            'status' => 'approved',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertNotSame(hash('sha256', 'token-lama-yang-sudah-kedaluwarsa'), $loan->token_hash);
        $this->assertTrue($loan->expires_at->isFuture());
        $this->assertNull($loan->revoked_at);
        $this->assertStringContainsString('<svg', $loan->qr_svg);
    }

}
