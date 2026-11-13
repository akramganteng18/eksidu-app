# PROJECT HANDOVER — EKSiDU

**Pembaruan:** 13 November 2026  
**Source basis:** `eksidu-app(1).zip` yang dilampirkan pemilik pada sesi ini.  
**Tujuan:** melanjutkan proyek EKSiDU lama; bukan membuat proyek baru.

## 1. Status saat handover

Alur terpadu **Ajukan Layanan** dan **Arsip Dokumen Siswa** telah dipatch pada source kerja. Pemeriksaan statis pada sesi ini lulus, termasuk syntax lint, static checker, route list, dan JavaScript syntax. PHPUnit, migration MySQL, Vite production build, Composer validation, dan browser UAT belum dapat dijalankan di lingkungan audit karena keterbatasan PHP/Node/Composer platform. Status yang benar: **siap untuk pengujian integrasi/UAT di Windows/XAMPP**, belum dinyatakan siap produksi.

## 2. Teknologi dan arsitektur yang dipertahankan

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 10.50.3; PHP 8.1+ |
| Database | MySQL/MariaDB; SQLite in-memory untuk PHPUnit |
| UI | Blade, Tailwind 3.4, Alpine.js 3 |
| Build | Vite 6.4.5 |
| QR | simple-qrcode + signed URL + token hash |
| Export | `App\Support\XlsxWriter` dan halaman cetak browser |
| Lokal | Windows, XAMPP, phpMyAdmin, PowerShell |

## 3. Model alur baru

### Siswa

Siswa memakai satu form Ajukan Layanan: Rapor, TKA, SKLS, Ijazah, atau Legalisir. Legalisir meminta kategori dokumen. Pilihan bentuk Fisik/Digital dibatasi pada arsip yang dimiliki akun siswa dan status ketersediaan terkini. Rapor memerlukan tahun ajaran, semester, dan periode tengah/akhir. Pengajuan ganda aktif untuk arsip/bentuk yang sama ditolak di dalam transaksi dengan row lock.

### Admin/TU

Admin memakai Arsip Dokumen Siswa untuk mengelola metadata, file privat, status digital dan fisik, lokasi fisik, versi, histori, standar kelengkapan dinamis, dan pengecualian Tidak Berlaku dengan alasan. Status digital tersedia mensyaratkan file benar-benar ada. Penghapusan arsip menggunakan soft delete dan ditolak jika masih ada pengajuan aktif.

### QR dan file

- Digital: pengajuan menyimpan snapshot `document_version_id`; hasil legalisir punya `result_document_id`; token acak tersimpan hash + terenkripsi, QR bertanda tangan, berulang, dapat dicabut/reissue, QR lama invalid.
- Fisik: QR signed hanya ditampilkan kepada petugas Admin yang login. Kode manual di-hash, berlaku 14 hari, dapat dirotasi, dan dihapus setelah konfirmasi satu kali.
- Preview/download memakai otorisasi pemilik/Admin, disk privat, no-store, nosniff, dan referrer policy.

## 4. Migration baru

- `2026_11_13_000001_create_student_document_archives.php`
- `2026_11_13_000002_create_student_archive_requirements.php`
- `2026_11_13_000003_create_student_archive_requirement_exemptions.php`

Migration bersifat aditif; `down()` tidak menghapus arsip/histori. Hanya dokumen lama dengan kode `IJZ` yang dipetakan otomatis ke Ijazah. SKL/SKHUN tidak dipetakan ke SKLS. Rapor lama tanpa periode tidak dipetakan otomatis. Periksa migration dan data pada database uji sebelum produksi.

## 5. Data lama dan transisi

Tabel `documents`, `document_loans`, `physical_inventory_items`, dan `physical_loans` tetap ada untuk mempertahankan data historis. Menu lama diarahkan ke menu baru dan transaksi baru melalui controller lama diblokir/diarahkan. Sebagian akses QR/download legacy tetap dipertahankan agar histori yang sudah disetujui tidak hilang. Jangan menghapus tabel lama tanpa persetujuan dan audit data.

## 6. Pemeriksaan sesi 13 November

- Static checker: 156 PHP, 78 view, 18 komponen, 105 referensi route; tidak ada temuan.
- `php artisan route:list --json`: 123 route.
- Blade compiler + PHP lint: 78 view lulus kompilasi dan syntax lint.
- Test regresi tambahan: sintaks diperiksa untuk validasi aturan duplikat, metadata kategori, dan penyelesaian fisik; eksekusi masih menunggu PHP extensions.
- PHP syntax lint: 152 file lulus pada source PHP yang diperiksa.
- JavaScript `node --check`: lulus.
- `npm audit --offline`: 0 vulnerabilities dilaporkan (hasil offline).
- PHPUnit tidak dapat dimulai: ekstensi `dom`, `mbstring`, `xmlwriter` tidak tersedia.
- MySQL migration tidak dijalankan: driver PDO MySQL/SQLite tidak tersedia.
- Vite build tidak dapat dijalankan: `node_modules` berasal dari Windows; binary Vite tidak executable di Linux dan Rollup Linux native optional dependency tidak ada.
- Composer validation tidak dijalankan: Composer CLI tidak tersedia.
- Browser UAT belum dijalankan pada sesi ini.

Lihat `FINAL_TEST_REPORT.md` untuk detail dan perintah verifikasi Windows/XAMPP.

## 7. Aturan keselamatan data

1. Jangan jalankan `migrate:fresh` atau `db:wipe` pada database yang berisi data.
2. Jangan mengganti `.env` atau APP_KEY saat update.
3. Jangan menghapus `storage/app/berkas` atau file dokumen lama.
4. Jangan mengemas `.env`, file siswa, storage privat, log, session, cache, `vendor`, atau `node_modules` ke ZIP distribusi.
5. Backup database dan storage secara bersamaan; panduan ada di `PANDUAN_BACKUP_RESTORE_XAMPP.md`.
6. Jangan menganggap fitur/test lulus tanpa menjalankan pengujian pada target Windows/XAMPP.

## 8. Prioritas penerus

1. Jalankan tiga migration baru pada salinan database uji.
2. Jalankan seluruh PHPUnit dan perbaiki regresi, jangan menghapus test gagal.
3. Jalankan `npm ci`, `npm audit`, `npm.cmd run build` pada Windows.
4. Uji seluruh alur Rapor/TKA/SKLS/Ijazah/Legalisir fisik/digital.
5. Uji IDOR, token QR, expiry, revoke/reissue, version snapshot, upload file, dan akses file privat.
6. Uji backup/restore dan concurrency pada MySQL.
7. Lengkapi browser UAT desktop/tablet/HP dan tema Light/Dark.
8. Konfigurasikan malware scanner dan backup off-site bila kebijakan sekolah mewajibkan.
