<?php

namespace Tests\Feature;

use App\Models\Pengajuan;
use App\Models\DocumentType;
use App\Models\StudentDocumentArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BerkasPrivatTest extends TestCase
{
    use RefreshDatabase;

    private function pengajuanDenganBerkas($pemilik, string $path = 'berkas-pengajuan/dok.pdf'): Pengajuan
    {
        Storage::disk('berkas')->put($path, '%PDF-1.4 isi rahasia');

        return Pengajuan::factory()->for($pemilik, 'user')->create(['file_pendukung' => $path]);
    }

    public function test_unggahan_siswa_disimpan_di_disk_privat_bukan_publik(): void
    {
        Storage::fake('berkas');
        Storage::fake('public');
        $siswa = $this->buatSiswa();
        $identityKey = hash('sha256', json_encode([$siswa->id, 'tka', '', '', '', '2026'], JSON_UNESCAPED_SLASHES));
        $archive = StudentDocumentArchive::create([
            'user_id' => $siswa->id,
            'identity_key' => $identityKey,
            'category' => 'tka',
            'publication_year' => 2026,
            'digital_status' => 'belum_diunggah',
            'physical_status' => 'tersedia',
            'version' => 1,
        ]);

        $this->actingAs($siswa)->post(route('siswa.pengajuan.store'), [
            'kategori_layanan' => 'tka',
            'bentuk_dokumen' => 'fisik',
            'archive_id' => $archive->id,
            'keperluan' => 'Keperluan uji',
            'file_pendukung' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $pengajuan = Pengajuan::firstOrFail();
        Storage::disk('berkas')->assertExists($pengajuan->file_pendukung);
        Storage::disk('public')->assertMissing($pengajuan->file_pendukung);
    }

    public function test_pemilik_dapat_membuka_berkasnya_sendiri(): void
    {
        Storage::fake('berkas');
        $pemilik = $this->buatSiswa();
        $pengajuan = $this->pengajuanDenganBerkas($pemilik);

        $respons = $this->actingAs($pemilik)->get(route('berkas.pengajuan', $pengajuan));

        $respons->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $respons->headers->get('Content-Disposition'));
        $respons->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_unduh_memaksa_attachment(): void
    {
        Storage::fake('berkas');
        $pemilik = $this->buatSiswa();
        $pengajuan = $this->pengajuanDenganBerkas($pemilik);

        $respons = $this->actingAs($pemilik)->get(route('berkas.pengajuan', [$pengajuan, 'unduh' => 1]));

        $this->assertStringContainsString('attachment', $respons->headers->get('Content-Disposition'));
    }

    public function test_siswa_lain_tidak_bisa_membuka_berkas_dengan_mengganti_id(): void
    {
        Storage::fake('berkas');
        $pengajuan = $this->pengajuanDenganBerkas($this->buatSiswa());

        // 404 (bukan 403) agar keberadaan berkas tidak bisa ditebak.
        $this->actingAs($this->buatSiswa())->get(route('berkas.pengajuan', $pengajuan))->assertNotFound();
    }

    public function test_admin_dapat_membuka_berkas_siswa(): void
    {
        Storage::fake('berkas');
        $pengajuan = $this->pengajuanDenganBerkas($this->buatSiswa());

        $this->actingAs($this->buatAdmin())->get(route('berkas.pengajuan', $pengajuan))->assertOk();
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        Storage::fake('berkas');
        $pengajuan = $this->pengajuanDenganBerkas($this->buatSiswa());

        $this->get(route('berkas.pengajuan', $pengajuan))->assertRedirect('/login');
    }

    public function test_berkas_lama_di_disk_publik_tetap_dilayani_hanya_lewat_pemeriksaan_izin(): void
    {
        Storage::fake('berkas');
        Storage::fake('public');
        Storage::disk('public')->put('berkas-pengajuan/lama.pdf', '%PDF-1.4 lama');
        $pemilik = $this->buatSiswa();
        $pengajuan = Pengajuan::factory()->for($pemilik, 'user')->create(['file_pendukung' => 'berkas-pengajuan/lama.pdf']);

        $this->actingAs($pemilik)->get(route('berkas.pengajuan', $pengajuan))->assertOk();
        $this->actingAs($this->buatSiswa())->get(route('berkas.pengajuan', $pengajuan))->assertNotFound();
    }

    public function test_pengajuan_tanpa_berkas_menghasilkan_404(): void
    {
        $pemilik = $this->buatSiswa();
        $pengajuan = Pengajuan::factory()->for($pemilik, 'user')->create();

        $this->actingAs($pemilik)->get(route('berkas.pengajuan', $pengajuan))->assertNotFound();
    }

    /** @return array<string, array{0:UploadedFile}> */
    public static function berkasTidakValid(): array
    {
        return [
            'skrip php' => [UploadedFile::fake()->create('shell.php', 10, 'text/x-php')],
            'executable' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')],
            'html' => [UploadedFile::fake()->create('halaman.html', 10, 'text/html')],
            'svg berskrip' => [UploadedFile::fake()->create('gambar.svg', 10, 'image/svg+xml')],
            'terlalu besar' => [UploadedFile::fake()->create('besar.pdf', 3000, 'application/pdf')],
        ];
    }

    /**
     * @dataProvider berkasTidakValid
     */
    public function test_berkas_berbahaya_atau_terlalu_besar_ditolak(UploadedFile $berkas): void
    {
        Storage::fake('berkas');
        $jenisDokumen = DocumentType::factory()->create(['layanan' => ['peminjaman_rapor']]);

        $this->actingAs($this->buatSiswa())->post(route('siswa.pengajuan.store'), [
            'jenis_layanan' => 'peminjaman_rapor',
            'document_type_id' => $jenisDokumen->id,
            'keperluan' => 'Uji validasi berkas',
            'file_pendukung' => $berkas,
        ])->assertSessionHasErrors('file_pendukung');

        $this->assertSame(0, Pengajuan::count());
        $this->assertSame([], Storage::disk('berkas')->allFiles());
    }
}
