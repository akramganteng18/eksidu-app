<?php

namespace Tests\Feature;

use App\Models\Pengajuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentitasDanTemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_identitas_aplikasi_adalah_eksidu(): void
    {
        $this->assertSame('EKSiDU', config('app.name'));
        $this->assertSame('EKSiDU', config('eksidu.nama'));
        $this->assertSame('id', config('app.locale'));
    }

    public function test_halaman_utama_memakai_nama_eksidu_dan_tanpa_nama_lama(): void
    {
        $siswa = $this->buatSiswa();
        Pengajuan::factory()->for($siswa, 'user')->create();
        $admin = $this->buatAdmin();

        $halaman = [
            [null, '/login'],
            [$siswa, '/dashboard'], [$siswa, '/pengajuan'], [$siswa, '/pengajuan/buat'], [$siswa, '/profil'],
            [$siswa, '/notifikasi'], [$siswa, '/panduan'],
            [$admin, '/dashboard'], [$admin, '/admin/pengajuan'], [$admin, '/admin/siswa'], [$admin, '/admin/dokumen'],
            [$admin, '/admin/laporan'], [$admin, '/admin/aktivitas'], [$admin, '/admin/panduan'], [$admin, '/admin/scan-qr'],
            [null, '/tidak-ada-halaman-ini'],
        ];

        foreach ($halaman as [$pengguna, $url]) {
            $respons = $pengguna ? $this->actingAs($pengguna)->get($url) : $this->get($url);
            $html = $respons->getContent();

            $this->assertStringContainsString('EKSiDU', $html, "{$url} tidak memuat nama EKSiDU");
            $this->assertStringNotContainsStringIgnoringCase('simada', $html, "{$url} masih memuat nama lama");
            $this->assertStringContainsString('<html lang="id"', $html, "{$url} lang bukan id");
            $this->assertMatchesRegularExpression('/<title>[^<]+EKSiDU<\/title>/u', $html, "{$url} judul tab salah");
        }
    }

    public function test_sistem_tema_terpasang_di_semua_jenis_layout(): void
    {
        $siswa = $this->buatSiswa();

        foreach ([
            $this->get('/login'),                         // layout guest
            $this->actingAs($siswa)->get('/dashboard'),   // layout aplikasi
            $this->get('/tidak-ada-halaman-ini'),         // layout error
        ] as $respons) {
            $respons->assertSee("localStorage.getItem('eksidu-theme')", false);   // init anti-kilat
            $respons->assertSee('prefers-color-scheme: dark', false);             // preferensi OS
            $respons->assertSee('x-data="themeToggle"', false);                   // tombol ganti tema
            $respons->assertSee('data-theme="light"', false);                     // nilai awal tanpa JS
        }
    }

    public function test_skrip_tema_dimuat_sebelum_aset_css(): void
    {
        $html = $this->get('/login')->getContent();

        $this->assertNotFalse(strpos($html, 'eksidu-theme'));
        $this->assertNotFalse(strpos($html, '</head>'));
        $this->assertLessThan(strpos($html, '</head>'), strpos($html, 'eksidu-theme'), 'skrip tema harus berada di <head>');
    }

    public function test_pesan_validasi_berbahasa_indonesia(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);

        $this->assertStringContainsString('wajib diisi', session('errors')->first('email'));
    }

    public function test_tidak_ada_sisa_nama_lama_di_kode_sumber(): void
    {
        // Rujukan "SIMADA" yang SENGAJA dipertahankan (kompatibilitas tiket lama), didokumentasikan:
        $diizinkan = [
            'config/eksidu.php',                                    // komentar: tiket lama berawalan SIMADA
            'app/Http/Controllers/Siswa/PengajuanController.php',   // komentar: tiket lama tetap bisa dilacak
            'resources/views/siswa/pengajuan/lacak.blade.php',      // petunjuk pengguna soal tiket lama
        ];

        $ditemukan = [];
        $akar = base_path();
        foreach (['app', 'resources', 'routes', 'config', 'database', 'lang'] as $folder) {
            $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("{$akar}/{$folder}", \FilesystemIterator::SKIP_DOTS));
            foreach ($iter as $berkas) {
                if (! $berkas->isFile() || ! preg_match('/\.(php|js|css|json)$/', $berkas->getFilename())) {
                    continue;
                }
                if (stripos(file_get_contents($berkas->getPathname()), 'simada') !== false) {
                    $ditemukan[] = str_replace('\\', '/', substr($berkas->getPathname(), strlen($akar) + 1));
                }
            }
        }

        $this->assertSame([], array_values(array_diff($ditemukan, $diizinkan)), 'Ada rujukan SIMADA yang tidak terdokumentasi');
    }

    public function test_tampilan_tidak_memakai_warna_mentah_di_luar_sistem_token(): void
    {
        // Menjaga konsistensi dua tema: komponen wajib memakai token (bg-surface, text-ink, tone-*, dst.).
        $pelanggaran = [];
        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS));
        foreach ($iter as $berkas) {
            if (! str_ends_with($berkas->getFilename(), '.blade.php')) {
                continue;
            }
            $isi = file_get_contents($berkas->getPathname());
            if (preg_match('/\b(?:bg|text|border|ring|from|to|via)-(?:indigo|blue|gray|zinc|neutral|stone|red|orange|yellow|lime|emerald|cyan|teal|purple|pink|fuchsia)-\d{2,3}\b/', $isi, $m)
                || preg_match('/\bdark:/', $isi)) {
                $pelanggaran[] = basename($berkas->getPathname());
            }
        }

        $this->assertSame([], $pelanggaran, 'Pakai token tema, bukan warna Tailwind mentah / varian dark:');
    }
}
