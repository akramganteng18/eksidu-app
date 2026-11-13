<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\Pengajuan;
use App\Models\Document;
use App\Models\StudentDocumentArchive;
use App\Models\User;
use App\Notifications\PengajuanStatusUpdated;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PengajuanAlurTest extends TestCase
{
    use RefreshDatabase;















    private function buatArsip(User $siswa, array $data = []): StudentDocumentArchive
    {
        $category = $data['category'] ?? 'rapor';
        $academicYear = $data['academic_year'] ?? '2026/2027';
        $semester = $data['semester'] ?? 1;
        $period = $data['report_period'] ?? 'akhir';
        return StudentDocumentArchive::create(array_merge([
            'user_id' => $siswa->id,
            'identity_key' => hash('sha256', json_encode([$siswa->id, $category, $academicYear, $semester, $period, ''])),
            'category' => $category, 'academic_year' => $academicYear, 'semester' => $semester,
            'report_period' => $period, 'digital_status' => 'belum_diunggah', 'physical_status' => 'tersedia', 'version' => 1,
        ], $data));
    }

    public function test_siswa_mengajukan_dokumen_fisik_dari_arsip_pribadi(): void
    {
        $siswa = $this->buatSiswa();
        $archive = $this->buatArsip($siswa);
        $response = $this->actingAs($siswa)->post(route('siswa.pengajuan.store'), [
            'kategori_layanan' => 'rapor', 'bentuk_dokumen' => 'fisik', 'archive_id' => $archive->id,
            'semester_akademik' => 1, 'periode_rapor' => 'akhir', 'keperluan' => 'Keperluan administrasi sekolah',
        ]);
        $pengajuan = Pengajuan::firstOrFail();
        $response->assertRedirect(route('siswa.pengajuan.show', $pengajuan))->assertSessionHasNoErrors();
        $this->assertSame($siswa->id, $pengajuan->user_id);
        $this->assertSame($archive->id, $pengajuan->archive_id);
        $this->assertSame('fisik', $pengajuan->bentuk_dokumen);
        $this->assertDatabaseHas('pengajuan_riwayats', ['pengajuan_id' => $pengajuan->id, 'status_ke' => 'pending']);
    }

    public function test_siswa_tidak_dapat_mengajukan_arsip_milik_siswa_lain(): void
    {
        $pemilik = $this->buatSiswa();
        $lain = $this->buatSiswa();
        $archive = $this->buatArsip($pemilik);
        $this->actingAs($lain)->post(route('siswa.pengajuan.store'), [
            'kategori_layanan' => 'rapor', 'bentuk_dokumen' => 'fisik', 'archive_id' => $archive->id,
            'semester_akademik' => 1, 'periode_rapor' => 'akhir', 'keperluan' => 'Uji akses silang',
        ])->assertNotFound();
        $this->assertSame(0, Pengajuan::count());
    }

    public function test_pengajuan_digital_hanya_bisa_dibuat_jika_file_ada(): void
    {
        $siswa = $this->buatSiswa();
        $archive = $this->buatArsip($siswa, ['digital_status' => 'tersedia']);
        $this->actingAs($siswa)->post(route('siswa.pengajuan.store'), [
            'kategori_layanan' => 'rapor', 'bentuk_dokumen' => 'digital', 'archive_id' => $archive->id,
            'semester_akademik' => 1, 'periode_rapor' => 'akhir', 'keperluan' => 'Uji dokumen digital',
        ])->assertSessionHasErrors('bentuk_dokumen');
        $this->assertSame(0, Pengajuan::count());
    }

    public function test_pengajuan_ganda_untuk_arsip_dan_bentuk_yang_sama_ditolak(): void
    {
        $siswa = $this->buatSiswa();
        $archive = $this->buatArsip($siswa);
        $data = ['kategori_layanan' => 'rapor', 'bentuk_dokumen' => 'fisik', 'archive_id' => $archive->id,
            'semester_akademik' => 1, 'periode_rapor' => 'akhir', 'keperluan' => 'Pengajuan pertama'];
        $this->actingAs($siswa)->post(route('siswa.pengajuan.store'), $data)->assertSessionHasNoErrors();
        $this->actingAs($siswa)->post(route('siswa.pengajuan.store'), $data)->assertSessionHasErrors('archive_id');
        $this->assertSame(1, Pengajuan::count());
    }

    public function test_form_ajukan_layanan_hanya_menyajikan_arsip_milik_siswa(): void
    {
        $siswa = $this->buatSiswa();
        $this->buatArsip($siswa);
        $this->buatArsip($this->buatSiswa());
        $this->actingAs($siswa)->get(route('siswa.pengajuan.create'))->assertOk()
            ->assertViewHas('archives', fn ($archives) => $archives->count() === 1);
    }

    public function test_halaman_detail_tampil_untuk_siswa_dan_admin_regresi_cast_tanggal(): void
    {
        // Regresi: cast tanggal dulu memakai method casts() (Laravel 11) sehingga di Laravel 10
        // atribut tetap string dan ->format()/->translatedFormat() melempar fatal error.
        $siswa = $this->buatSiswa();
        $pengajuan = Pengajuan::factory()->for($siswa, 'user')->status('selesai')->create([
            'tanggal_dibutuhkan' => '2026-12-01',
            'tanggal_pengembalian' => '2026-12-05',
            'verified_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $pengajuan->fresh()->tanggal_dibutuhkan);
        $this->assertInstanceOf(Carbon::class, $pengajuan->fresh()->verified_at);

        $this->actingAs($siswa)->get(route('siswa.pengajuan.show', $pengajuan))->assertOk()->assertSee('Desember 2026');
        $this->actingAs($this->buatAdmin())->get(route('admin.pengajuan.show', $pengajuan))->assertOk()->assertSee('Desember 2026');
    }

    public function test_siswa_tidak_bisa_melihat_pengajuan_milik_siswa_lain(): void
    {
        $pengajuan = Pengajuan::factory()->for($this->buatSiswa(), 'user')->create();

        $this->actingAs($this->buatSiswa())->get(route('siswa.pengajuan.show', $pengajuan))->assertNotFound();
    }

    public function test_pelacakan_hanya_menemukan_tiket_milik_sendiri_termasuk_tiket_lama_simada(): void
    {
        $a = $this->buatSiswa();
        $b = $this->buatSiswa();
        $milikA = Pengajuan::factory()->for($a, 'user')->create(['kode_tiket' => 'SIMADA-2025-000042']);
        $milikB = Pengajuan::factory()->for($b, 'user')->create();

        // huruf kecil pun dikenali; tiket lama berawalan SIMADA tetap bisa dilacak
        $this->actingAs($a)->post(route('siswa.pengajuan.lacak.cari'), ['kode_tiket' => ' simada-2025-000042 '])
            ->assertRedirect(route('siswa.pengajuan.show', $milikA));
        $this->actingAs($a)->post(route('siswa.pengajuan.lacak.cari'), ['kode_tiket' => $milikB->kode_tiket])
            ->assertSessionHasErrors('kode_tiket');
    }

    public function test_admin_menjalankan_alur_status_yang_valid_dan_menolak_lompatan(): void
    {
        $siswa = $this->buatSiswa();
        $admin = $this->buatAdmin();
        $pengajuan = Pengajuan::factory()->for($siswa, 'user')->create();
        $this->actingAs($admin);

        // pending -> approved adalah lompatan terlarang
        $this->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'approved'])->assertSessionHasErrors('status');
        $this->assertSame('pending', $pengajuan->fresh()->status);

        $this->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'diproses'])->assertSessionHasNoErrors();
        $this->assertSame('diproses', $pengajuan->fresh()->status);

        $this->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $pengajuan->fresh()->status);
        $this->assertSame($admin->id, $pengajuan->fresh()->verified_by);

        // Riwayat tercatat lengkap & siswa menerima notifikasi tiap perubahan.
        $this->assertSame(
            ['diproses', 'approved'],
            $pengajuan->riwayats()->whereNotNull('status_dari')->pluck('status_ke')->all()
        );
        $this->assertCount(2, $siswa->fresh()->notifications);
    }

    public function test_penolakan_wajib_disertai_alasan_dan_status_final_tidak_bisa_diubah(): void
    {
        $pengajuan = Pengajuan::factory()->for($this->buatSiswa(), 'user')->status('diproses')->create();
        $this->actingAs($this->buatAdmin());

        $this->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'rejected'])->assertSessionHasErrors('catatan_admin');
        $this->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'rejected', 'catatan_admin' => 'Berkas tidak lengkap'])
            ->assertSessionHasNoErrors();

        $this->assertSame('rejected', $pengajuan->fresh()->status);
        $this->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'diproses'])->assertSessionHasErrors('status');
        $this->assertSame('Berkas tidak lengkap', $pengajuan->fresh()->catatan_admin);
    }

    public function test_status_di_luar_daftar_ditolak_validasi(): void
    {
        $pengajuan = Pengajuan::factory()->for($this->buatSiswa(), 'user')->create();

        $this->actingAs($this->buatAdmin())->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'hacked'])
            ->assertSessionHasErrors('status');
    }


    public function test_pengajuan_fisik_baru_tidak_bisa_langsung_selesai_tanpa_serah_terima(): void
    {
        $siswa = $this->buatSiswa();
        $admin = $this->buatAdmin();
        $archive = \App\Models\StudentDocumentArchive::create([
            'user_id' => $siswa->id, 'identity_key' => hash('sha256', 'physical-finish-guard'),
            'category' => 'ijazah', 'digital_status' => 'belum_diunggah', 'physical_status' => 'tersedia', 'version' => 1,
        ]);
        $pengajuan = Pengajuan::factory()->for($siswa, 'user')->status('approved')->create([
            'archive_id' => $archive->id, 'bentuk_dokumen' => 'fisik', 'kategori_layanan' => 'ijazah',
            'jenis_layanan' => 'pengambilan_ijazah',
            'physical_verification_hash' => hash('sha256', 'EKSIDU-TESTCODE123456'),
            'physical_verification_code' => Crypt::encryptString('EKSIDU-TESTCODE123456'),
            'physical_verification_expires_at' => now()->addDays(3),
        ]);

        $this->actingAs($admin)->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'selesai'])
            ->assertSessionHasErrors('status');
        $this->assertSame('approved', $pengajuan->fresh()->status);
        $this->assertNotNull($pengajuan->fresh()->physical_verification_hash);
    }

    public function test_pencatatan_pengembalian_hanya_untuk_status_siap_diambil(): void
    {
        $this->actingAs($this->buatAdmin());
        $belumSiap = Pengajuan::factory()->for($this->buatSiswa(), 'user')->status('diproses')->create();
        $siap = Pengajuan::factory()->for($this->buatSiswa(), 'user')->status('approved')->create();
        $data = ['tanggal_pengembalian' => now()->toDateString(), 'kondisi_pengembalian' => 'Baik'];

        $this->put(route('admin.pengajuan.pengembalian', $belumSiap), $data)->assertSessionHasErrors('status');
        $this->assertSame('diproses', $belumSiap->fresh()->status);

        $this->put(route('admin.pengajuan.pengembalian', $siap), $data)->assertSessionHasNoErrors();
        $this->assertSame('selesai', $siap->fresh()->status);
    }

    public function test_qr_hanya_tampil_untuk_pengajuan_siap_diambil(): void
    {
        $siswa = $this->buatSiswa();
        $code = 'EKSIDU-TESTCODE123456';
        $siap = Pengajuan::factory()->for($siswa, 'user')->status('approved')->create(['bentuk_dokumen' => 'fisik', 'physical_verification_hash' => hash('sha256', $code), 'physical_verification_code' => Crypt::encryptString($code), 'physical_verification_expires_at' => now()->addDays(3)]);
        $pending = Pengajuan::factory()->for($siswa, 'user')->create(['jenis_layanan' => 'legalisir']);

        $this->actingAs($siswa)->get(route('siswa.pengajuan.show', $siap))
            ->assertOk()->assertSee('QR Verifikasi Pengambilan')->assertSee('<svg', false);
        $this->actingAs($siswa)->get(route('siswa.pengajuan.show', $pending))
            ->assertOk()->assertDontSee('QR Verifikasi Pengambilan');
    }

    public function test_alur_konfirmasi_qr_aman(): void
    {
        $siswa = $this->buatSiswa();
        $admin = $this->buatAdmin();
        $dokumen = DocumentType::factory()->create(['nama' => 'Ijazah Asli']);
        $code = 'EKSIDU-TESTCODE123456';
        $pengajuan = Pengajuan::factory()->for($siswa, 'user')->status('approved')->create(['document_type_id' => $dokumen->id, 'bentuk_dokumen' => 'fisik', 'physical_verification_hash' => hash('sha256', $code), 'physical_verification_code' => Crypt::encryptString($code), 'physical_verification_expires_at' => now()->addDays(3)]);
        $ttd = URL::temporarySignedRoute('pengajuan.qr.show', now()->addDay(), ['pengajuan' => $pengajuan->id, 'v' => substr($pengajuan->physical_verification_hash, 0, 16)], false);

        // Tanpa tanda tangan -> ditolak
        $this->actingAs($admin)->get(route('pengajuan.qr.show', $pengajuan))->assertForbidden();

        // QR fisik tidak membocorkan data ke akun siswa lain; hanya petugas Admin/TU yang dapat memverifikasi.
        $this->actingAs($siswa)->get($ttd)->assertRedirect(route('login'));

        // GET hanya MENAMPILKAN; Admin mendapat tombol konfirmasi, siswa tidak.
        $this->actingAs($admin)->get($ttd)->assertOk()->assertSee('Konfirmasi pengambilan');
        $this->assertSame('approved', $pengajuan->fresh()->status);

        // Perubahan hanya lewat POST
        $this->post(route('admin.pengajuan.qrKonfirmasi', $pengajuan))->assertRedirect(route('admin.scan-qr'));
        $this->assertSame('selesai', $pengajuan->fresh()->status);
        $this->assertNotNull($pengajuan->fresh()->tanggal_pengembalian);

        // Konfirmasi ganda ditolak dengan pesan
        $this->post(route('admin.pengajuan.qrKonfirmasi', $pengajuan))->assertSessionHas('error');
    }

    public function test_qr_fisik_meminta_petugas_login_sebelum_menampilkan_data(): void
    {
        $siswa = $this->buatSiswa();
        $code = 'EKSIDU-TESTCODE123456';
        $pengajuan = Pengajuan::factory()->for($siswa, 'user')->status('approved')->create(['bentuk_dokumen' => 'fisik', 'physical_verification_hash' => hash('sha256', $code), 'physical_verification_code' => Crypt::encryptString($code), 'physical_verification_expires_at' => now()->addDays(3)]);
        $url = URL::temporarySignedRoute('pengajuan.qr.show', now()->addDays(7), ['pengajuan' => $pengajuan->id, 'v' => substr($pengajuan->physical_verification_hash, 0, 16)], false);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->buatAdmin())->get($url)->assertOk()->assertSee('Konfirmasi pengambilan');
        $this->assertSame('approved', $pengajuan->fresh()->status);
    }

    public function test_halaman_scan_admin_mengenali_rute_qr_pengajuan_baru(): void
    {
        $this->actingAs($this->buatAdmin())->get(route('admin.scan-qr'))
            ->assertOk()->assertSee("url.pathname.includes('/pengajuan/qr/')", false)->assertSee('configuredQrBase', false);
    }

    public function test_qr_kedaluwarsa_atau_dirusak_ditolak_dengan_pesan_ramah(): void
    {
        $pengajuan = Pengajuan::factory()->for($this->buatSiswa(), 'user')->status('approved')->create();
        $admin = $this->buatAdmin();

        $kedaluwarsa = URL::temporarySignedRoute('pengajuan.qr.show', now()->subMinute(), ['pengajuan' => $pengajuan->id], false);
        $this->actingAs($admin)->get($kedaluwarsa)->assertForbidden()->assertSee('kedaluwarsa');

        $dirusak = URL::signedRoute('pengajuan.qr.show', ['pengajuan' => $pengajuan->id], false) . '0';
        $this->actingAs($admin)->get($dirusak)->assertForbidden();
    }

    public function test_pencarian_manual_kode_tiket_di_halaman_scan(): void
    {
        $pengajuan = Pengajuan::factory()->for($this->buatSiswa(), 'user')->status('approved')->create();
        $this->actingAs($this->buatAdmin());

        $this->post(route('admin.scan-qr.cari'), ['kode_tiket' => strtolower($pengajuan->kode_tiket)])
            ->assertRedirect(route('admin.pengajuan.show', $pengajuan));
        $this->post(route('admin.scan-qr.cari'), ['kode_tiket' => 'EKSIDU-0000-000000'])
            ->assertSessionHasErrors('kode_tiket');
    }

    public function test_notifikasi_dapat_dibuka_dan_ditandai_dibaca(): void
    {
        $siswa = $this->buatSiswa();
        $pengajuan = Pengajuan::factory()->for($siswa, 'user')->create();
        $this->actingAs($this->buatAdmin())->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'diproses']);

        $notifikasi = $siswa->fresh()->unreadNotifications->first();
        $this->assertNotNull($notifikasi);

        $this->actingAs($siswa)->get(route('notifications.index'))->assertOk()->assertSee($pengajuan->kode_tiket);
        $this->actingAs($siswa)->get(route('notifications.open', $notifikasi->id))
            ->assertRedirect(route('siswa.pengajuan.show', $pengajuan));
        $this->assertSame(0, $siswa->fresh()->unreadNotifications()->count());
    }

    public function test_siswa_lain_tidak_dapat_membuka_notifikasi_orang_lain(): void
    {
        $siswa = $this->buatSiswa();
        $pengajuan = Pengajuan::factory()->for($siswa, 'user')->create();
        $this->actingAs($this->buatAdmin())->put(route('admin.pengajuan.status', $pengajuan), ['status' => 'diproses']);
        $notifikasi = $siswa->fresh()->unreadNotifications->first();

        $this->actingAs($this->buatSiswa())->get(route('notifications.open', $notifikasi->id))->assertNotFound();
    }
    public function test_notifikasi_email_opsional_tidak_aktif_secara_bawaan(): void
    {
        $siswa = $this->buatSiswa();
        $pengajuan = Pengajuan::factory()->for($siswa, 'user')->create();
        $notification = new PengajuanStatusUpdated($pengajuan);

        config(['eksidu.notifikasi_email' => false]);
        $this->assertSame(['database'], $notification->via($siswa));

        config(['eksidu.notifikasi_email' => true]);
        $this->assertSame(['database', 'mail'], $notification->via($siswa));
    }

}
