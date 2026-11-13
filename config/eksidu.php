<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi khusus EKSiDU
|--------------------------------------------------------------------------
| Identitas aplikasi sengaja dipisah dari APP_NAME di .env supaya nama merek
| tetap konsisten walaupun file .env lama masih berisi nilai bawaan "Laravel".
*/

return [
    'nama'            => 'EKSiDU',
    'nama_panjang'    => 'Sistem Manajemen Dokumen Akademik Siswa',
    'tagline'         => 'Layanan dokumen akademik yang cepat, transparan, dan aman.',
    'sekolah'         => 'SMK Negeri 5 Surakarta',
    'sekolah_singkat' => 'SMKN 5 Surakarta',

    // Alamat kanonis untuk URL di dalam QR. Harus dapat dijangkau HP pemindai;
    // isi EKSIDU_PUBLIC_URL dengan domain HTTPS atau IP LAN yang sesuai.
    'public_url' => rtrim((string) (env('EKSIDU_PUBLIC_URL') ?: env('APP_URL', 'http://localhost')), '/'),

    // Awalan nomor tiket untuk pengajuan BARU. Tiket lama ("SIMADA-...") tetap
    // utuh di database dan tetap bisa dilacak.
    'prefix_tiket'    => 'EKSIDU',

    // Registrasi mandiri ditutup secara bawaan: NIS tidak diverifikasi terhadap
    // data sekolah, sehingga akun siswa sebaiknya dibuat oleh Admin/TU.
    'registrasi_publik' => (bool) env('EKSIDU_REGISTRASI_PUBLIK', false),

    // Menampilkan kotak "akun demo" di halaman login. Hanya untuk pengembangan.
    'akun_demo'       => (bool) env('EKSIDU_AKUN_DEMO', false),
    'demo_password'   => env('EKSIDU_DEMO_PASSWORD', 'password'),

    // Email tambahan hanya aktif jika SMTP sudah dikonfigurasi dan flag diaktifkan.
    'notifikasi_email' => (bool) env('EKSIDU_NOTIFIKASI_EMAIL', false),

    // Akun admin awal (dipakai AdminSeeder).
    'admin' => [
        'nama'     => env('ADMIN_NAME', 'Admin TU SMKN 5 Surakarta'),
        'email'    => env('ADMIN_EMAIL', 'admin@smkn5surakarta.sch.id'),
        'password' => env('ADMIN_PASSWORD'),
        'nip'      => env('ADMIN_NIP'),
    ],

    // Penyimpanan berkas privat (tidak bisa diakses lewat URL publik).
    'disk_berkas'     => 'berkas',
    'batas_berkas_kb' => 2048,
    'batas_media_kb'  => (int) env('EKSIDU_BATAS_MEDIA_KB', 51200),
];
