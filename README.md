# EKSiDU — Ekosistem Digital Sekolah

Aplikasi manajemen arsip dokumen akademik dan layanan siswa untuk **SMK Negeri 5 Surakarta**. Ini merupakan kelanjutan proyek EKSiDU lama, bukan proyek Laravel baru.

> **Status patch 13 November 2026:** alur terpadu Ajukan Layanan dan Arsip Dokumen Siswa sudah diimplementasikan pada source. Pemeriksaan sintaks PHP, static checker, route list, dan JavaScript syntax lulus pada lingkungan audit. PHPUnit, migration MySQL, Vite build, dan browser UAT belum dapat diulang pada lingkungan ini. Lihat `FINAL_TEST_REPORT.md` sebelum menyatakan rilis siap produksi.

## Alur utama

- **Siswa:** satu menu Ajukan Layanan untuk Rapor, TKA, SKLS, Ijazah, dan Legalisir. Pilihan Fisik/Digital hanya dapat dipilih jika arsip milik siswa tersebut tersedia.
- **Admin/TU:** satu menu Arsip Dokumen Siswa untuk membuat, memperbarui, mengunggah, mengarsipkan, mengatur status fisik/digital, melihat histori, menetapkan standar kelengkapan, dan memberi pengecualian Tidak Berlaku per siswa.
- **Pengajuan:** tiket, status, histori, notifikasi, QR fisik/kode manual, QR digital berulang dengan token yang dapat dicabut dan diterbitkan ulang.
- **Legalisir digital:** hasil legalisir disimpan sebagai file terpisah; file asli tidak ditimpa.
- Modul repository/peminjaman lama tidak lagi menerima transaksi baru dari menu lama. Data dan histori lamanya tetap dipertahankan.

## Teknologi

- Laravel 10.50.3; PHP 8.1+.
- MySQL/MariaDB untuk aplikasi; PHPUnit menggunakan SQLite in-memory.
- Blade, Tailwind CSS 3.4, Alpine.js 3, Vite 6.4.5.
- Composer 2; Node.js 18+ / npm untuk build aset.

Ekstensi PHP yang diperlukan antara lain `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, `dom`, `xml`, `xmlwriter`, dan `pdo_sqlite` untuk test.

## Instalasi baru Windows/XAMPP

1. Ekstrak ZIP dan buka PowerShell pada folder yang berisi `artisan`.
2. Buat database baru di phpMyAdmin, misalnya `eksidu`, charset `utf8mb4`, collation `utf8mb4_unicode_ci`.
3. Salin `.env.example` menjadi `.env`; isi DB, disk privat, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` yang kuat.
4. Jalankan:

```powershell
composer install
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan db:seed
php artisan serve
```

5. Buka `http://127.0.0.1:8000`. Akun siswa dibuat oleh Admin melalui Data Siswa atau impor CSV.

Aset produksi yang ada di `public/build` disertakan. Jika ingin membangun ulang aset setelah perubahan CSS/JS:

```powershell
npm ci
npm audit
npm.cmd run build
```

## Update instalasi lama

- Backup database dan `storage/app/berkas` terlebih dahulu.
- Jangan mengganti `.env`, APP_KEY, database, atau file pengguna.
- Ekstrak source ke folder staging, lalu salin perubahan source dengan hati-hati.
- Periksa `php artisan migrate:status`, lalu jalankan `php artisan migrate` setelah backup dan persetujuan.
- Jangan pernah menjalankan `migrate:fresh` atau `db:wipe` pada database yang berisi data.

## Pengujian

```powershell
php artisan migrate:status
php artisan route:list
php artisan test
npm audit
npm.cmd run build
```

Lanjutkan dengan checklist browser di `PANDUAN_RUN_DAN_PENGUJIAN_EKSiDU.md` dan `FINAL_TEST_REPORT.md`. Untuk backup/pemulihan, lihat `PANDUAN_BACKUP_RESTORE_XAMPP.md`.

## Dokumen handover

- `PROJECT_HANDOVER.md`
- `MASTER_PROMPT_AUDIT.md`
- `DAFTAR_FITUR_EKSiDU.md`
- `DATABASE_SETUP.md`
- `PANDUAN_UPDATE_XAMPP.md`
- `PANDUAN_BACKUP_RESTORE_XAMPP.md`
- `PANDUAN_QR_DARI_HP.md`
- `FINAL_TEST_REPORT.md`
- `TODO.md`
- `CHANGELOG.md`

## Keamanan dan privasi

Dokumen disimpan pada disk privat, bukan `public`. `.env`, kredensial, data siswa, dan file privat tidak boleh dimasukkan ke paket distribusi. Antivirus upload dan backup otomatis/off-site belum dikonfigurasi; ikuti catatan batasan pada laporan pengujian.
