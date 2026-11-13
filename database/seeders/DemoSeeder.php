<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\Pengajuan;
use App\Models\PengajuanRiwayat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data demo FIKTIF untuk pengembangan/pengujian (bukan data pribadi siswa sungguhan).
 * Idempoten: akun dicari berdasarkan email, pengajuan hanya dibuat bila siswa belum punya.
 * Jalankan manual:  php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder ditolak di lingkungan produksi.');

            return;
        }

        $admin = User::where('role', 'admin')->orderBy('id')->first();
        $password = Hash::make(config('eksidu.demo_password'));

        $siswa = [
            ['Ahmad Fauzan Nugroho', 'ahmad.fauzan@siswa.smkn5surakarta.sch.id', '2223010001', 'XII RPL 1'],
            ['Bunga Citra Lestari', 'bunga.citra@siswa.smkn5surakarta.sch.id', '2223010004', 'XII RPL 1'],
            ['Dimas Prasetyo Wibowo', 'dimas.prasetyo@siswa.smkn5surakarta.sch.id', '2223010012', 'XII RPL 2'],
            ['Elok Ramadhani Putri', 'elok.ramadhani@siswa.smkn5surakarta.sch.id', '2223010020', 'XII RPL 2'],
            ['Faisal Rahman Hidayat', 'faisal.rahman@siswa.smkn5surakarta.sch.id', '2223010028', 'XII RPL 3'],
        ];

        // Skenario tetap (deterministik): [jenis, kode dokumen, status akhir, hari lalu]
        $skenario = [
            0 => [['legalisir', 'IJZ', 'selesai', 40], ['peminjaman_rapor', 'RPR', 'approved', 3]],
            1 => [['pengambilan_ijazah', 'IJZ', 'diproses', 2], ['legalisir', 'SKHUN', 'rejected', 25]],
            2 => [['peminjaman_rapor', 'RPR', 'pending', 1]],
            3 => [['legalisir', 'SKL', 'selesai', 12], ['pengambilan_ijazah', 'IJZ', 'pending', 0]],
            4 => [],
        ];

        foreach ($siswa as $i => [$nama, $email, $nis, $kelas]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $nama, 'password' => $password, 'role' => 'siswa',
                    'nis_nip' => $nis, 'kelas' => $kelas, 'no_telp' => '0812000000' . ($i + 10), 'aktif' => true,
                ]
            );

            if ($user->pengajuans()->exists()) {
                continue;
            }

            foreach ($skenario[$i] ?? [] as [$jenis, $kodeDok, $status, $hariLalu]) {
                $this->buatPengajuan($user, $admin, $jenis, $kodeDok, $status, $hariLalu);
            }
        }

        $this->command?->info('Data demo siap. Kata sandi akun demo: nilai EKSIDU_DEMO_PASSWORD di .env.');
    }

    private function buatPengajuan(User $user, ?User $admin, string $jenis, string $kodeDok, string $status, int $hariLalu): void
    {
        $dibuat = now()->subDays($hariLalu)->setTime(9, 15);
        $dokumen = DocumentType::where('kode', $kodeDok)->first();

        $p = new Pengajuan([
            'user_id'            => $user->id,
            'jenis_layanan'      => $jenis,
            'document_type_id'   => $dokumen?->id,
            'keperluan'          => 'Keperluan pendaftaran kerja/kuliah dan administrasi lain (data demo).',
            'tanggal_dibutuhkan' => $dibuat->copy()->addDays(7)->toDateString(),
            'jumlah_lembar'      => $jenis === 'legalisir' ? 2 : null,
            'status'             => $status,
        ]);
        $p->forceFill(['created_at' => $dibuat, 'updated_at' => $dibuat])->save();

        $jalur = [
            'pending'  => ['pending'],
            'diproses' => ['pending', 'diproses'],
            'approved' => ['pending', 'diproses', 'approved'],
            'selesai'  => ['pending', 'diproses', 'approved', 'selesai'],
            'rejected' => ['pending', 'diproses', 'rejected'],
        ][$status];

        $dari = null;
        foreach ($jalur as $n => $tahap) {
            PengajuanRiwayat::create([
                'pengajuan_id' => $p->id,
                'user_id'      => $tahap === 'pending' ? $user->id : $admin?->id,
                'status_dari'  => $dari,
                'status_ke'    => $tahap,
                'catatan'      => $tahap === 'pending' ? 'Pengajuan dibuat.' : null,
                'created_at'   => $dibuat->copy()->addHours($n * 5),
            ]);
            $dari = $tahap;
        }

        if ($status !== 'pending') {
            $extra = ['verified_by' => $admin?->id, 'verified_at' => $dibuat->copy()->addHours(count($jalur) * 5)];
            if ($status === 'rejected') {
                $extra['catatan_admin'] = 'Berkas pendukung belum lengkap. Silakan ajukan kembali (data demo).';
            }
            if ($status === 'selesai') {
                $extra['tanggal_pengembalian'] = $dibuat->copy()->addDays(2)->toDateString();
                $extra['kondisi_pengembalian'] = 'Baik';
            }
            $p->forceFill($extra)->save();
        }
    }
}
