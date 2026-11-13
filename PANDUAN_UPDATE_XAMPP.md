# Panduan Instalasi Baru dan Update EKSiDU — Windows/XAMPP

**Paket:** `EKSiDU_FINAL_2026-11-13.zip`  
**Source:** Laravel EKSiDU yang sudah ada; bukan proyek baru.

> Patch 13 November menambahkan arsip per siswa dan pengajuan terpadu. Backup database serta `storage/app/berkas` sebelum menjalankan migration. Jangan menjalankan `migrate:fresh`, `db:wipe`, atau mengganti APP_KEY pada instalasi lama.

## A. Persiapan

1. Jalankan MySQL dari XAMPP.
2. Pastikan PowerShell memakai PHP XAMPP:

```powershell
where.exe php
php -v
php -m
```

3. Aktifkan ekstensi PHP: `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `zip`, `dom`, `xml`, `xmlwriter`.
4. Pasang Composer 2. Node.js 18+ dan npm dibutuhkan untuk membangun ulang aset.

## B. Instalasi baru (database kosong)

1. Ekstrak ZIP ke `C:\xampp\htdocs\eksidu-app`.
2. Buka phpMyAdmin (`http://localhost/phpmyadmin`) → Databases → buat `eksidu` dengan collation `utf8mb4_unicode_ci`.
3. Pada PowerShell:

```powershell
cd C:\xampp\htdocs\eksidu-app
Copy-Item .env.example .env
```

4. Edit `.env`:

```dotenv
APP_NAME=EKSiDU
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
EKSIDU_PUBLIC_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eksidu
DB_USERNAME=root
DB_PASSWORD=
ADMIN_NAME="Admin TU SMKN 5 Surakarta"
ADMIN_EMAIL=admin@smkn5surakarta.sch.id
ADMIN_PASSWORD=GANTI_DENGAN_PASSWORD_KUAT
EKSIDU_REGISTRASI_PUBLIK=false
EKSIDU_AKUN_DEMO=false
EKSIDU_NOTIFIKASI_EMAIL=false
```

Gunakan kredensial lokal Anda sendiri. Jangan menggunakan password contoh pada server sekolah.

5. Jalankan:

```powershell
composer install
php artisan key:generate
php artisan config:clear
php artisan migrate:status
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

6. Buka `http://127.0.0.1:8000`. Admin awal dibuat dari `ADMIN_EMAIL` dan `ADMIN_PASSWORD` bila belum ada. Buat akun siswa melalui menu Data Siswa.
7. `public/build` sudah disertakan. Bila mengubah CSS/JS:

```powershell
npm ci
npm audit
npm.cmd run build
```

## C. Update instalasi EKSiDU lama

1. Hentikan sementara aktivitas pengguna.
2. Backup database dan seluruh `storage/app` ke lokasi di luar `htdocs`. Lihat `PANDUAN_BACKUP_RESTORE_XAMPP.md`.
3. Simpan `.env` dan APP_KEY lama. Jangan menggantinya.
4. Ekstrak ZIP ke folder staging, misalnya `C:\xampp\htdocs\eksidu-update`.
5. Salin source ke instalasi setelah membandingkan perubahan. Jangan menimpa `storage/app`, `.env`, atau file upload.
6. Pastikan `.env` lama tetap menunjuk ke database yang benar, lalu jalankan:

```powershell
composer install
php artisan config:clear
php artisan migrate:status
```

7. Pastikan backup valid dan migration baru belum tercatat. Jalankan:

```powershell
php artisan migrate
```

8. Jangan menjalankan `db:seed` pada update kecuali ada instruksi seed yang memang diperlukan dan sudah ditinjau.
9. Jalankan `php artisan route:list`, test, dan UAT browser. Build ulang aset bila CSS/JS berubah.

## D. Migration baru 13 November 2026

- `2026_11_13_000001_create_student_document_archives.php`
- `2026_11_13_000002_create_student_archive_requirements.php`
- `2026_11_13_000003_create_student_archive_requirement_exemptions.php`

Migration bersifat aditif. Hanya kode dokumen lama `IJZ` yang dipetakan otomatis ke Ijazah. SKL/SKHUN tidak disamakan otomatis dengan SKLS; Rapor lama tanpa periode tengah/akhir tidak dipetakan otomatis. Admin perlu memeriksa dan membuat arsip yang belum terpetakan.

## E. Menjalankan pengujian

```powershell
php artisan migrate:status
php artisan route:list
php artisan test
php artisan test --filter=StudentDocumentArchiveTest
php artisan test --filter=PengajuanAlurTest
npm audit
npm.cmd run build
```

Jangan menandai test lulus tanpa output sukses dari komputer target. Lihat `FINAL_TEST_REPORT.md` untuk batas verifikasi pada patch ini.

## F. Menyiapkan QR untuk HP

- QR menggunakan `EKSIDU_PUBLIC_URL`. Untuk Wi-Fi lokal, gunakan alamat LAN PC, bukan `localhost` atau `127.0.0.1`.
- Pastikan HP dan PC berada pada jaringan yang dapat saling terhubung dan port server diizinkan firewall.
- Untuk produksi gunakan domain HTTPS resmi.
- QR digital memerlukan login dan hanya pemilik/Admin yang berwenang dapat mengakses file. QR dapat digunakan berulang kali selama token aktif; Admin dapat mencabut dan menerbitkan ulang.
- QR fisik memerlukan Admin/TU login. Kode manual berlaku 14 hari dan dapat dirotasi.
- Jangan menonaktifkan middleware signed/auth atau memindahkan dokumen ke folder `public`.

Panduan lengkap: `PANDUAN_QR_DARI_HP.md`.

## G. Larangan untuk database berisi data

- Jangan mengganti `.env` atau APP_KEY.
- Jangan menghapus `storage/app/berkas`.
- Jangan menjalankan `migrate:fresh`, `db:wipe`, atau rollback destruktif.
- Jangan menimpa file dokumen pengguna dengan isi ZIP.
- Jangan menyebarkan backup, QR, token, password, atau dokumen siswa.

## H. Status verifikasi

Pada sesi patch 13 November, 152 file PHP lolos syntax lint, 78 Blade view lolos compile + PHP lint, static checker lulus, 123 route terdaftar, 4 file JavaScript lolos `node --check`, dan `npm audit --offline` melaporkan 0 vulnerabilities. PHPUnit, migration MySQL, Vite build, Composer validation, dan browser UAT belum dapat diulang di lingkungan audit. Lihat `FINAL_TEST_REPORT.md`.
