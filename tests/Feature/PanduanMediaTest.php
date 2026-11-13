<?php

namespace Tests\Feature;

use App\Models\PanduanMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PanduanMediaTest extends TestCase
{
    use RefreshDatabase;

    private function media(array $atribut = []): PanduanMedia
    {
        return PanduanMedia::create(array_merge([
            'judul' => 'Panduan Uji', 'jenis' => 'video', 'sumber' => 'unggah',
            'file_path' => 'panduan/uji.mp4', 'mime' => 'video/mp4', 'untuk' => 'semua', 'aktif' => true, 'urutan' => 0,
        ], $atribut));
    }

    public function test_admin_mengunggah_video_ke_disk_privat(): void
    {
        Storage::fake('berkas');
        Storage::fake('public');

        $this->actingAs($this->buatAdmin())->post(route('admin.panduan.store'), [
            'judul' => 'Cara Mengajukan Legalisir', 'jenis' => 'video', 'sumber' => 'unggah', 'untuk' => 'semua',
            'urutan' => 1, 'aktif' => 1,
            'berkas' => UploadedFile::fake()->create('panduan.mp4', 500, 'video/mp4'),
        ])->assertSessionHasNoErrors();

        $media = PanduanMedia::firstOrFail();
        Storage::disk('berkas')->assertExists($media->file_path);
        Storage::disk('public')->assertMissing($media->file_path);
        $this->assertSame('video/mp4', $media->mime);
    }

    public function test_admin_mengunggah_audio(): void
    {
        Storage::fake('berkas');

        $this->actingAs($this->buatAdmin())->post(route('admin.panduan.store'), [
            'judul' => 'Audio Panduan', 'jenis' => 'audio', 'sumber' => 'unggah', 'untuk' => 'siswa', 'aktif' => 1,
            'berkas' => UploadedFile::fake()->create('info.mp3', 200, 'audio/mpeg'),
        ])->assertSessionHasNoErrors();

        $this->assertSame('audio', PanduanMedia::firstOrFail()->jenis);
    }

    /** @return array<string, array{0:UploadedFile,1:string}> */
    public static function unggahanTidakValid(): array
    {
        return [
            'executable' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'), 'video'],
            'skrip php' => [UploadedFile::fake()->create('x.php', 10, 'text/x-php'), 'video'],
            'audio dipakai untuk video' => [UploadedFile::fake()->create('lagu.mp3', 10, 'audio/mpeg'), 'video'],
            'video dipakai untuk audio' => [UploadedFile::fake()->create('klip.mp4', 10, 'video/mp4'), 'audio'],
            'terlalu besar' => [UploadedFile::fake()->create('raksasa.mp4', 60000, 'video/mp4'), 'video'],
        ];
    }

    /**
     * @dataProvider unggahanTidakValid
     */
    public function test_unggahan_tidak_valid_ditolak(UploadedFile $berkas, string $jenis): void
    {
        Storage::fake('berkas');

        $this->actingAs($this->buatAdmin())->post(route('admin.panduan.store'), [
            'judul' => 'Uji', 'jenis' => $jenis, 'sumber' => 'unggah', 'untuk' => 'semua', 'berkas' => $berkas,
        ])->assertSessionHasErrors('berkas');

        $this->assertSame(0, PanduanMedia::count());
        $this->assertSame([], Storage::disk('berkas')->allFiles());
    }

    public function test_berkas_wajib_saat_membuat_materi_unggahan(): void
    {
        $this->actingAs($this->buatAdmin())->post(route('admin.panduan.store'), [
            'judul' => 'Tanpa berkas', 'jenis' => 'video', 'sumber' => 'unggah', 'untuk' => 'semua',
        ])->assertSessionHasErrors('berkas');
    }

    public function test_tautan_youtube_valid_disimpan_dan_ditanam_lewat_nocookie(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->post(route('admin.panduan.store'), [
            'judul' => 'Video YouTube', 'jenis' => 'audio', // dipaksa menjadi video
            'sumber' => 'tautan', 'url' => 'https://youtu.be/dQw4w9WgXcQ', 'untuk' => 'semua', 'aktif' => 1,
        ])->assertSessionHasNoErrors();

        $media = PanduanMedia::firstOrFail();
        $this->assertSame('video', $media->jenis);
        $this->assertNull($media->file_path);

        $this->actingAs($admin)->get(route('panduan.show', $media))
            ->assertOk()->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
    }

    /** @return array<string, array{0:string}> */
    public static function tautanTidakValid(): array
    {
        return [
            'domain lain' => ['https://evil.example/watch?v=dQw4w9WgXcQ'],
            'skema javascript' => ['javascript:alert(1)'],
            'id terlalu pendek' => ['https://youtu.be/abc'],
            'menumpang domain' => ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ'],
            'teks biasa' => ['bukan url'],
        ];
    }

    /**
     * @dataProvider tautanTidakValid
     */
    public function test_tautan_selain_youtube_ditolak(string $url): void
    {
        $this->actingAs($this->buatAdmin())->post(route('admin.panduan.store'), [
            'judul' => 'Uji', 'jenis' => 'video', 'sumber' => 'tautan', 'url' => $url, 'untuk' => 'semua',
        ])->assertSessionHasErrors('url');

        $this->assertSame(0, PanduanMedia::count());
    }

    public function test_siswa_tidak_bisa_mengelola_materi(): void
    {
        $siswa = $this->buatSiswa();
        $media = $this->media();

        $this->actingAs($siswa)->post(route('admin.panduan.store'), ['judul' => 'x'])->assertForbidden();
        $this->actingAs($siswa)->put(route('admin.panduan.update', $media), ['judul' => 'x'])->assertForbidden();
        $this->actingAs($siswa)->delete(route('admin.panduan.destroy', $media))->assertForbidden();
        $this->assertDatabaseHas('panduan_media', ['id' => $media->id]);
    }

    public function test_pembatasan_akses_berdasarkan_sasaran_dan_status(): void
    {
        Storage::fake('berkas');
        $untukSemua = $this->media(['judul' => 'Untuk Semua']);
        $untukAdmin = $this->media(['judul' => 'Rahasia Admin', 'untuk' => 'admin']);
        $nonaktif = $this->media(['judul' => 'Belum Rilis', 'aktif' => false]);
        $siswa = $this->buatSiswa();

        $daftar = $this->actingAs($siswa)->get(route('panduan.index'));
        $daftar->assertOk()->assertSee('Untuk Semua')->assertDontSee('Rahasia Admin')->assertDontSee('Belum Rilis');

        $this->actingAs($siswa)->get(route('panduan.show', $untukSemua))->assertOk();
        // Mengganti ID di URL tidak membuka materi yang bukan hak siswa
        $this->actingAs($siswa)->get(route('panduan.show', $untukAdmin))->assertNotFound();
        $this->actingAs($siswa)->get(route('panduan.stream', $untukAdmin))->assertNotFound();
        $this->actingAs($siswa)->get(route('panduan.show', $nonaktif))->assertNotFound();

        $admin = $this->buatAdmin();
        $this->actingAs($admin)->get(route('panduan.show', $untukAdmin))->assertOk();
        $this->actingAs($admin)->get(route('panduan.index'))->assertSee('Rahasia Admin')->assertSee('Belum Rilis');
    }

    public function test_streaming_media_lewat_controller_dengan_izin(): void
    {
        Storage::fake('berkas');
        Storage::disk('berkas')->put('panduan/uji.mp4', str_repeat('x', 2048));
        $media = $this->media();

        $this->get(route('panduan.stream', $media))->assertRedirect('/login');

        $respons = $this->actingAs($this->buatSiswa())->get(route('panduan.stream', $media));
        $respons->assertOk()->assertHeader('Content-Type', 'video/mp4')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_streaming_mendukung_permintaan_rentang_untuk_seek(): void
    {
        Storage::fake('berkas');
        Storage::disk('berkas')->put('panduan/uji.mp4', str_repeat('x', 2048));
        $media = $this->media();

        $respons = $this->actingAs($this->buatSiswa())
            ->withHeaders(['Range' => 'bytes=0-99'])
            ->get(route('panduan.stream', $media));

        $respons->assertStatus(206);
    }

    public function test_berkas_hilang_menghasilkan_404_bukan_error(): void
    {
        Storage::fake('berkas');
        $media = $this->media(); // berkas tidak pernah ditulis

        $this->actingAs($this->buatSiswa())->get(route('panduan.stream', $media))->assertNotFound();
    }

    public function test_menghapus_materi_ikut_menghapus_berkasnya(): void
    {
        Storage::fake('berkas');
        Storage::disk('berkas')->put('panduan/uji.mp4', 'data');
        $media = $this->media();

        $this->actingAs($this->buatAdmin())->delete(route('admin.panduan.destroy', $media))->assertSessionHas('status');

        $this->assertDatabaseMissing('panduan_media', ['id' => $media->id]);
        Storage::disk('berkas')->assertMissing('panduan/uji.mp4');
    }

    public function test_mengganti_berkas_menghapus_berkas_lama(): void
    {
        Storage::fake('berkas');
        Storage::disk('berkas')->put('panduan/uji.mp4', 'lama');
        $media = $this->media();

        $this->actingAs($this->buatAdmin())->put(route('admin.panduan.update', $media), [
            'judul' => 'Judul Baru', 'jenis' => 'video', 'sumber' => 'unggah', 'untuk' => 'semua', 'aktif' => 1,
            'berkas' => UploadedFile::fake()->create('baru.mp4', 100, 'video/mp4'),
        ])->assertSessionHasNoErrors();

        Storage::disk('berkas')->assertMissing('panduan/uji.mp4');
        $this->assertNotSame('panduan/uji.mp4', $media->fresh()->file_path);
        Storage::disk('berkas')->assertExists($media->fresh()->file_path);
    }

    public function test_halaman_panduan_kosong_menampilkan_empty_state_bukan_pemutar_palsu(): void
    {
        $this->actingAs($this->buatSiswa())->get(route('panduan.index'))
            ->assertOk()->assertSee('Belum ada materi panduan')->assertDontSee('<video', false);
    }

    public function test_pemutar_video_unggahan_memiliki_fallback_dan_tanpa_autoplay(): void
    {
        $media = $this->media();

        $this->actingAs($this->buatSiswa())->get(route('panduan.show', $media))
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee('controls', false)
            ->assertDontSee('autoplay', false)
            ->assertSee('Video tidak dapat dimuat');
    }
}
