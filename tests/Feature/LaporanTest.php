<?php

namespace Tests\Feature;

use App\Models\Pengajuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    public function test_ekspor_excel_menghasilkan_xlsx_valid_dengan_data_terfilter(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('Ekstensi zip tidak aktif (aplikasi akan memakai cadangan CSV).');
        }

        $siswa = $this->buatSiswa(['name' => 'Rina Ekspor']);
        Pengajuan::factory()->for($siswa, 'user')->status('selesai')->create();
        Pengajuan::factory()->for($this->buatSiswa(), 'user')->create();

        $respons = $this->actingAs($this->buatAdmin())->get(route('admin.laporan.excel', ['status' => 'selesai']));

        $respons->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $respons->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $respons->headers->get('Content-Disposition'));

        $berkas = $respons->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($berkas) === true, 'XLSX harus berupa arsip ZIP yang valid');
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotFalse($sheet);
        $this->assertStringContainsString('Rina Ekspor', $sheet);          // data sesuai filter
        $this->assertStringContainsString('Kode Tiket', $sheet);           // header
        $this->assertSame(2, substr_count($sheet, '<row '));               // 1 header + 1 baris terfilter
        $zip->close();
    }

    public function test_halaman_cetak_menampilkan_data_dan_filter(): void
    {
        $siswa = $this->buatSiswa(['name' => 'Dina Cetak']);
        Pengajuan::factory()->for($siswa, 'user')->create();

        $this->actingAs($this->buatAdmin())->get(route('admin.laporan.cetak'))
            ->assertOk()
            ->assertSee('Laporan Pengajuan Layanan Dokumen')
            ->assertSee('Dina Cetak')
            ->assertSee('Cetak / Simpan PDF');
    }

    public function test_laporan_menghormati_filter_tanggal_dan_status(): void
    {
        $siswa = $this->buatSiswa();
        Pengajuan::factory()->for($siswa, 'user')->create(['created_at' => now()->subDays(40)]);
        Pengajuan::factory()->for($siswa, 'user')->status('selesai')->create();
        $this->actingAs($this->buatAdmin());

        $this->get(route('admin.laporan.index', ['dari' => now()->subDays(7)->toDateString()]))
            ->assertViewHas('pengajuans', fn ($p) => $p->total() === 1);
        $this->get(route('admin.laporan.index'))
            ->assertViewHas('perStatus', fn ($s) => (int) $s->sum() === 2);
    }

    public function test_siswa_tidak_bisa_mengekspor_laporan(): void
    {
        $siswa = $this->buatSiswa();

        $this->actingAs($siswa)->get(route('admin.laporan.excel'))->assertForbidden();
        $this->actingAs($siswa)->get(route('admin.laporan.cetak'))->assertForbidden();
    }
}
