<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['RPR', 'Buku Rapor Fisik', 'Buku rapor semester 1 s.d. 6 milik siswa aktif/alumni.', ['peminjaman_rapor', 'legalisir']],
            ['IJZ', 'Ijazah Asli', 'Ijazah kelulusan asli yang disimpan sekolah.', ['legalisir', 'pengambilan_ijazah']],
            ['SKHUN', 'SKHUN / Transkrip Nilai', 'Surat Keterangan Hasil Ujian Nasional atau transkrip nilai akhir.', ['legalisir']],
            ['SKL', 'Surat Keterangan Lulus', 'Dokumen sementara pengganti ijazah sebelum ijazah asli terbit.', []],
        ];

        foreach ($data as [$kode, $nama, $keterangan, $layanan]) {
            // firstOrCreate: tidak menimpa perubahan yang sudah dibuat Admin.
            DocumentType::firstOrCreate(
                ['kode' => $kode],
                ['nama' => $nama, 'keterangan' => $keterangan, 'aktif' => true, 'layanan' => $layanan]
            );
        }
    }
}
