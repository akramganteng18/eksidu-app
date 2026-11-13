# Panduan Menjalankan dan Pengujian EKSiDU — Windows/XAMPP

Panduan ini untuk ZIP patch 13 November 2026. Lakukan instalasi baru dan update lama dengan prosedur berbeda. Selalu gunakan database uji sebelum mencoba migration baru.

## A. Persiapan

1. Buka XAMPP Control Panel, aktifkan MySQL.
2. Pastikan PowerShell memakai PHP XAMPP:

```powershell
where.exe php
php -v
php -m
```

Aktifkan extension `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `zip`, `dom`, `xml`, `xmlwriter` pada `php.ini`, lalu restart terminal. Composer 2 wajib tersedia. Node.js 18+ dan npm diperlukan untuk audit/build frontend.

## B. Instalasi baru untuk pengujian

1. Ekstrak ZIP ke `C:\xampp\htdocs\eksidu-app`.
2. Buka `http://localhost/phpmyadmin` → Databases → buat `eksidu_uji` dengan `utf8mb4_unicode_ci`.
3. Di PowerShell:

```powershell
cd C:\xampp\htdocs\eksidu-app
Copy-Item .env.example .env
```

4. Edit `.env`:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eksidu_uji
DB_USERNAME=root
DB_PASSWORD=
EKSIDU_AKUN_DEMO=false
```

Atur `ADMIN_EMAIL` dan `ADMIN_PASSWORD` dengan kredensial uji yang kuat. Jangan memakai password contoh untuk penggunaan sekolah.

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

6. Buka `http://127.0.0.1:8000`. Login Admin dengan kredensial yang Anda konfigurasi. Buat akun siswa uji melalui menu Data Siswa.

Aset hasil build sudah disertakan pada `public/build`. Untuk membangun ulang:

```powershell
npm ci
npm audit
npm.cmd run build
```

## C. Update instalasi EKSiDU lama

1. Backup database dan `storage/app/berkas` sesuai `PANDUAN_BACKUP_RESTORE_XAMPP.md`.
2. Simpan `.env` lama dan APP_KEY. Jangan mengganti keduanya.
3. Ekstrak source ke folder staging terpisah. Jangan menimpa `storage/app/berkas`.
4. Salin source/dokumentasi baru ke folder aplikasi setelah backup dan pemeriksaan diff.
5. Jalankan:

```powershell
composer install
php artisan config:clear
php artisan migrate:status
```

6. Pastikan tiga migration arsip belum tercatat, lalu jalankan `php artisan migrate` setelah backup. Jangan menjalankan `migrate:fresh`, `db:wipe`, atau seed demo pada database berisi data.
7. Periksa log Laravel dan akses halaman Admin/Siswa.

## D. Pengujian otomatis

```powershell
php artisan test
php artisan test --filter=StudentDocumentArchiveTest
php artisan test --filter=PengajuanAlurTest
php artisan test --filter=PhysicalLoanTest
php artisan route:list
npm audit
npm.cmd run build
```

Pastikan semua command benar-benar selesai dengan exit code 0. Simpan output sebagai bukti pengujian; jangan menulis “lulus” bila command belum dijalankan.

## E. Checklist Admin/TU

- [ ] Buat standar kelengkapan untuk kelas/kategori/tahun ajaran/semester/periode.
- [ ] Buka Arsip Dokumen Siswa dan cari siswa.
- [ ] Tambah arsip Rapor, TKA, SKLS, Ijazah.
- [ ] Upload file PDF/JPG/PNG; pastikan file berada di disk privat.
- [ ] Ganti file dan pastikan versi lama tetap tersimpan.
- [ ] Ubah status digital dan fisik secara terpisah.
- [ ] Uji status Hilang/Rusak dan pastikan pengajuan fisik ditolak.
- [ ] Tandai satu syarat Tidak Berlaku dengan alasan; pastikan tidak dihitung sebagai kekurangan.
- [ ] Coba soft delete arsip yang memiliki pengajuan aktif; harus ditolak.
- [ ] Upload hasil legalisir digital; pastikan terpisah dari file asli.
- [ ] Setujui pengajuan, cabut/reissue QR digital, dan rotasi kode fisik.
- [ ] Periksa activity log, histori arsip, notifikasi, dashboard, dan laporan.

## F. Checklist Siswa

- [ ] Login sebagai siswa A; lihat hanya arsip milik akun A.
- [ ] Coba manipulasi `archive_id` milik siswa B; harus ditolak.
- [ ] Ajukan Rapor semester 1/2 dan periode tengah/akhir.
- [ ] Ajukan TKA, SKLS, Ijazah, dan Legalisir.
- [ ] Uji Fisik dan Digital berdasarkan ketersediaan.
- [ ] Ajukan dua kali untuk arsip/bentuk yang sama; pengajuan aktif kedua harus ditolak.
- [ ] Lihat status, tiket, histori, notifikasi.
- [ ] Untuk digital, buka QR beberapa kali, preview dan download beberapa kali.
- [ ] Setelah akses dicabut, QR lama harus ditolak; setelah reissue, QR baru berfungsi.
- [ ] Untuk fisik, tampilkan QR/kode manual; kode lama harus tidak berlaku setelah rotasi dan kode tidak dapat dipakai setelah selesai.

## G. Pemeriksaan keamanan

- [ ] Coba URL dokumen tanpa login.
- [ ] Coba akun siswa lain mengakses ID pengajuan/arsip.
- [ ] Coba token QR salah, kedaluwarsa, dan dicabut.
- [ ] Coba file yang bukan PDF/JPG/PNG dan ukuran di atas batas.
- [ ] Pastikan dokumen tidak berada di `public` dan tidak ada URL storage langsung.
- [ ] Pastikan petugas Admin harus login untuk verifikasi QR fisik.
- [ ] Periksa headers cache/no-sniff/referrer pada preview digital.

## H. Hal yang belum otomatis tersedia

- Pemindaian malware/antivirus upload belum terintegrasi.
- Backup otomatis/off-site belum dikonfigurasi.
- Uji concurrency MySQL, browser UAT, dan pemulihan backup harus dilakukan pada lingkungan target.

Jika ada error, simpan teks lengkap command dan `storage/logs/laravel.log` (hapus password/token sebelum membagikan). Jangan menghapus database atau file dokumen untuk mencoba memperbaiki error.
