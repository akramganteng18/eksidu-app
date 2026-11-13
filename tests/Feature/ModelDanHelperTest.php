<?php

namespace Tests\Feature;

use App\Models\PanduanMedia;
use App\Models\Pengajuan;
use App\Models\PengajuanRiwayat;
use App\Models\User;
use App\Support\Akademik;
use App\Support\XlsxWriter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use ZipArchive;

class ModelDanHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_kode_tiket_berurutan_per_tahun_dan_berawalan_eksidu(): void
    {
        $this->travelTo(Carbon::create(2026, 11, 4, 10));
        [$a, $b] = Pengajuan::factory()->count(2)->create();

        $this->assertSame('EKSIDU-2026-000001', $a->kode_tiket);
        $this->assertSame('EKSIDU-2026-000002', $b->kode_tiket);

        $this->travelTo(Carbon::create(2027, 1, 5, 9));
        $this->assertSame('EKSIDU-2027-000001', Pengajuan::factory()->create()->kode_tiket);
    }

    public function test_status_awal_model_baru_adalah_pending(): void
    {
        $this->assertSame('pending', (new Pengajuan())->status);
    }

    public function test_cast_tanggal_dan_boolean_berfungsi_di_laravel_10(): void
    {
        $p = Pengajuan::factory()->create(['tanggal_dibutuhkan' => '2026-12-01', 'verified_at' => '2026-11-02 08:00:00'])->fresh();
        $this->assertInstanceOf(Carbon::class, $p->tanggal_dibutuhkan);
        $this->assertInstanceOf(Carbon::class, $p->verified_at);
        $this->assertSame('01/12/2026', $p->tanggal_dibutuhkan->format('d/m/Y'));

        $u = User::factory()->create()->fresh();
        $this->assertIsBool($u->aktif);
        $this->assertTrue($u->aktif);
    }

    /** @return array<string, array{0:string,1:string,2:bool}> */
    public static function transisi(): array
    {
        $ok = [['pending', 'diproses'], ['diproses', 'approved'], ['diproses', 'rejected'], ['approved', 'selesai']];
        $semua = [];
        foreach (array_keys(Pengajuan::STATUS_LABEL) as $dari) {
            foreach (array_keys(Pengajuan::STATUS_LABEL) as $ke) {
                $semua["{$dari} -> {$ke}"] = [$dari, $ke, in_array([$dari, $ke], $ok, true)];
            }
        }

        return $semua;
    }

    /**
     * @dataProvider transisi
     */
    public function test_matriks_transisi_status(string $dari, string $ke, bool $diharapkan): void
    {
        $this->assertSame($diharapkan, (new Pengajuan(['status' => $dari]))->bisaBerpindahKe($ke));
    }

    public function test_label_dan_tone_status_konsisten(): void
    {
        foreach (Pengajuan::STATUS_LABEL as $kode => $label) {
            $p = new Pengajuan(['status' => $kode]);
            $this->assertSame($label, $p->statusLabel());
            $this->assertNotSame('slate', $p->statusTone(), "status {$kode} harus punya warna khusus");
        }
        $this->assertSame('Siap Diambil', (new Pengajuan(['status' => 'approved']))->statusLabel());
    }

    /** @return array<string, array{0:string,1:?string}> */
    public static function tautanYoutube(): array
    {
        $id = 'dQw4w9WgXcQ';

        return [
            'watch biasa' => ["https://www.youtube.com/watch?v={$id}", $id],
            'watch dengan parameter lain' => ["https://youtube.com/watch?feature=share&v={$id}&t=10s", $id],
            'youtu.be' => ["https://youtu.be/{$id}", $id],
            'youtu.be tanpa skema' => ["youtu.be/{$id}", $id],
            'embed' => ["https://www.youtube.com/embed/{$id}", $id],
            'shorts mobile' => ["https://m.youtube.com/shorts/{$id}", $id],
            'domain lain' => ["https://evil.example/watch?v={$id}", null],
            'subdomain menumpang' => ["https://youtube.com.evil.example/watch?v={$id}", null],
            'url di dalam url' => ["https://evil.example/https://youtube.com/watch?v={$id}", null],
            'injeksi atribut' => ["https://www.youtube.com/watch?v={$id}\"onload=\"alert(1)", null],
            'id kepanjangan' => ["https://youtu.be/{$id}extra", null],
            'id terlalu pendek' => ['https://youtu.be/abc', null],
            'kosong' => ['', null],
        ];
    }

    /**
     * @dataProvider tautanYoutube
     */
    public function test_ekstraksi_id_youtube_hanya_untuk_bentuk_yang_sah(string $url, ?string $diharapkan): void
    {
        $this->assertSame($diharapkan, PanduanMedia::ekstrakIdYoutube($url));
    }

    public function test_url_embed_selalu_memakai_domain_nocookie(): void
    {
        $media = new PanduanMedia(['url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0', $media->urlEmbed());
        $this->assertNull((new PanduanMedia(['url' => 'https://evil.example/x']))->urlEmbed());
    }

    public function test_tahun_ajaran_dimulai_bulan_juli(): void
    {
        $this->assertSame('2026/2027', Akademik::tahunAjaran(Carbon::create(2026, 7, 1)));
        $this->assertSame('2026/2027', Akademik::tahunAjaran(Carbon::create(2026, 12, 31)));
        $this->assertSame('2026/2027', Akademik::tahunAjaran(Carbon::create(2027, 1, 1)));
        $this->assertSame('2025/2026', Akademik::tahunAjaran(Carbon::create(2026, 6, 30)));
    }

    public function test_like_aman_meloloskan_wildcard(): void
    {
        $this->assertSame('%50\%\_a\\\\%', Akademik::likeAman('50%_a\\'));
        $this->assertSame('%budi%', Akademik::likeAman('budi'));
    }

    public function test_inisial_pengguna(): void
    {
        $this->assertSame('AF', (new User(['name' => 'Ahmad Fauzan Nugroho']))->initials());
        $this->assertSame('B', (new User(['name' => 'Budi']))->initials());
        $this->assertSame('U', (new User(['name' => '   ']))->initials());
    }

    public function test_riwayat_status_tercatat_dan_terurut(): void
    {
        $p = Pengajuan::factory()->create();
        PengajuanRiwayat::catat($p, null, 'pending');
        PengajuanRiwayat::catat($p, 'pending', 'diproses', null, 'Sedang dicek');

        $this->assertSame(['pending', 'diproses'], $p->riwayats->pluck('status_ke')->all());
        $this->assertSame('Sedang dicek', $p->riwayats->last()->catatan);
    }

    public function test_kebijakan_pengajuan_pemilik_admin_dan_orang_lain(): void
    {
        $pemilik = User::factory()->create();
        $lain = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $p = Pengajuan::factory()->for($pemilik, 'user')->create();

        $this->assertTrue(Gate::forUser($pemilik)->allows('view', $p));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $p));
        $this->assertTrue(Gate::forUser($admin)->allows('viewBerkas', $p));

        $hasil = Gate::forUser($lain)->inspect('view', $p);
        $this->assertFalse($hasil->allowed());
        $this->assertSame(404, $hasil->status(), 'orang lain mendapat 404, bukan 403');
    }

    public function test_penulis_xlsx_menghasilkan_arsip_valid_dengan_escape_aman(): void
    {
        if (! XlsxWriter::tersedia()) {
            $this->markTestSkipped('Ekstensi zip tidak aktif.');
        }

        $berkas = XlsxWriter::buat(['Nama', 'Catatan'], [
            ['Ahmad & "Budi" <x>', '=SUM(A1)'],
            ["Çağla – 日本語", "baris\nbaru\x07ok"],
        ], 'Uji');

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($berkas) === true);
        foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml'] as $bagian) {
            $xml = $zip->getFromName($bagian);
            $this->assertNotFalse($xml, "bagian {$bagian} hilang");
            $this->assertNotFalse(simplexml_load_string($xml), "bagian {$bagian} bukan XML yang valid");
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('Ahmad &amp; &quot;Budi&quot; &lt;x&gt;', $sheet);
        $this->assertStringContainsString('t="inlineStr"', $sheet);            // rumus tidak dievaluasi
        $this->assertStringNotContainsString("\x07", $sheet);                  // karakter kontrol dibuang
        $zip->close();
        @unlink($berkas);
    }
}
