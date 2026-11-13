# FINAL TEST REPORT — EKSiDU Unified Archive & Service Flow

**Tanggal patch:** 13 November 2026  
**Basis source:** `eksidu-app(1).zip` yang dilampirkan pada percakapan ini.  
**Status:** implementasi terpadu sudah dipatch pada source kerja; belum boleh disebut lulus UAT atau siap produksi sampai pengujian Windows/XAMPP selesai.

## 1. Ringkasan perubahan yang diaudit

- Menambahkan arsip akademik per siswa melalui `student_document_archives`.
- Menambahkan histori perubahan arsip melalui `student_document_archive_histories`.
- Menambahkan aturan kelengkapan dinamis per kelas/kategori/tahun ajaran/semester/periode melalui `student_archive_requirements`.
- Menambahkan pengecualian persyaratan per siswa dengan alasan dan soft delete melalui `student_archive_requirement_exemptions`.
- Menambahkan referensi arsip, bentuk dokumen, semester/periode, snapshot versi file, hash dan token QR pada `pengajuans`.
- Mengarahkan siswa ke satu alur **Ajukan Layanan** untuk Rapor, TKA, SKLS, Ijazah, dan Legalisir; pilihan fisik/digital mengikuti ketersediaan arsip milik akun tersebut.
- Menambahkan menu Admin **Arsip Dokumen Siswa**, upload privat, edit metadata, versi file, status fisik/digital, histori, standar kelengkapan, dan status Tidak Berlaku.
- Menambahkan hasil legalisir digital sebagai dokumen terpisah dari file asli.
- QR digital menggunakan token acak yang disimpan dalam bentuk hash + terenkripsi, URL bertanda tangan, dapat dibuka berulang kali, dan dapat dicabut/diterbitkan ulang sehingga QR lama tidak berlaku.
- QR fisik memerlukan Admin login; kode manual di-hash, masa berlaku 14 hari, dapat dirotasi, dan dihapus setelah konfirmasi serah terima.
- Menu repository/peminjaman lama diarahkan ke alur baru atau dibatasi untuk transaksi baru; tabel dan riwayat lama tetap dipertahankan.
- Laporan ditambah filter kategori, bentuk, tahun ajaran, semester, dan periode.

## 2. Pemeriksaan yang benar-benar dijalankan pada sesi ini

| Pemeriksaan | Hasil sesi ini | Keterangan |
|---|---|---|
| `php -l` pada source PHP | Lulus | 152 file PHP pada `app`, `bootstrap`, `config`, `database`, `routes`, dan `tests` diperiksa tanpa error sintaks |
| `tools/verifikasi/static_check.py` | Lulus | 156 PHP, 78 view, 18 komponen, 105 referensi route; tidak ada temuan setelah daftar route diperbarui |
| Blade compile + `php -l` hasil kompilasi | Lulus | 78 view berhasil dikompilasi dan hasil PHP-nya lolos lint |
| `php artisan route:list --json` | Lulus | 123 route terdaftar; route arsip, persyaratan, digital, legalisir, dan verifikasi tersedia |
| `node --check` | Lulus | File JavaScript aplikasi diperiksa tanpa error sintaks |
| `npm audit --offline --audit-level=high` | 0 vulnerabilities dilaporkan | Mode offline; bukan pengganti audit online terbaru di komputer target |
| `php artisan view:cache` | Tidak dapat dijalankan | CLI gagal pada `DOMDocument` yang tidak tersedia |
| PHPUnit (`php vendor/bin/phpunit`) | Tidak dapat dimulai | PHP CLI tidak memiliki `dom`, `mbstring`, `xmlwriter`; bukan hasil test gagal/lulus |
| `php artisan migrate` | Tidak dijalankan | Tidak ada driver PDO MySQL/SQLite pada lingkungan audit; migration harus dijalankan pada DB uji XAMPP |
| `npm run build` | Tidak dapat dijalankan | Skrip `vite` tidak memiliki izin eksekusi pada arsip node_modules lintas OS; pemanggilan Node langsung juga gagal karena optional dependency `@rollup/rollup-linux-x64-gnu` tidak ada |
| `composer validate` | Tidak dapat dijalankan | Binary Composer tidak tersedia pada lingkungan audit |
| Browser manual / desktop / tablet / HP | Belum diuji pada sesi ini | Harus diuji pada Windows/XAMPP oleh pemilik |

## 3. Hasil historis yang dilaporkan pemilik

Dokumentasi source yang dilampirkan sebelumnya mencatat hasil Windows/XAMPP pada 12 November: 231 PHPUnit tests / 817 assertions, 17 migration `Ran`, 105 route, `npm audit` 0 vulnerabilities, dan Vite build berhasil. **Angka historis tersebut berasal dari source dan alur sebelum patch terpadu 13 November. Angka itu tidak boleh dipakai sebagai bukti bahwa patch 13 November sudah lulus.**

## 4. Hasil uji Windows/XAMPP yang dilaporkan pemilik pada 13 November 2026

Pemilik menjalankan `composer install`, `php artisan migrate:status`, `php artisan migrate`, `php artisan test`, `php artisan test --filter=StudentDocumentArchiveTest`, dan `php artisan test --filter=PengajuanAlurTest` dari `C:\xampp\htdocs\eksidu-app`. Hasil yang terlihat pada log: Composer install berhasil dan tidak mengubah dependency; ketiga migration 13 November berstatus `DONE`; full suite menghasilkan **227 passed, 5 failed, 2 skipped (798 assertions)**; `StudentDocumentArchiveTest` menghasilkan **6 passed, 3 failed (37 assertions)**; `PengajuanAlurTest` menghasilkan **20 passed (97 assertions)**. Dua test dilewati karena ekstensi PHP ZIP tidak aktif, sehingga ekspor XLSX memakai fallback CSV.

Lima kegagalan yang terlihat dan tindak lanjut patch 1:

1. `BerkasPrivatTest` mengirim payload lama `jenis_layanan` dan `document_type_id`; test diperbarui untuk membuat arsip siswa dan memakai `kategori_layanan`, `bentuk_dokumen`, serta `archive_id` sesuai kontrak baru.
2. `ModelDanHelperTest` menemukan transisi `approved -> rejected` masih diizinkan; state machine disesuaikan dengan matriks alur agar pengajuan approved tidak ditolak langsung lewat transisi status umum.
3. `StudentDocumentArchiveTest` menemukan pemeriksaan aturan kelengkapan duplikat tidak cocok pada dimensi nullable; perbandingan sekarang menormalkan nilai null/kosong dan boolean secara eksplisit.
4. Dua tes QR digital menerima HTTP 500 ketika halaman preview menambahkan header pada objek View; controller kini membungkus view dengan `response()->view(...)` sebelum menetapkan header keamanan.

Log juga menunjukkan deprecation PHP 8.5 untuk `PDO::MYSQL_ATTR_SSL_CA` di `config/database.php`. Konfigurasi diperbarui agar menggunakan `Pdo\\Mysql::ATTR_SSL_CA` pada PHP 8.5 dan fallback pada versi sebelumnya.

**Hasil verifikasi ulang Patch 1 yang dilaporkan pemilik setelah menerapkan patch:**

| Test terpilih | Hasil | Keterangan |
|---|---:|---|
| `StudentDocumentArchiveTest` | 9 passed, 1 failed (45 assertions) | Satu kegagalan pada aturan kelengkapan duplikat |
| `BerkasPrivatTest` | 13 passed (37 assertions) | Semua test lulus |
| `ModelDanHelperTest` | 48 passed, 1 skipped (76 assertions) | Test XLSX dilewati karena ekstensi PHP ZIP belum aktif |

Akar kegagalan aturan duplikat ditemukan pada urutan route: `POST arsip-siswa/{siswa}` didaftarkan sebelum route statis `POST arsip-siswa/persyaratan`. Akibatnya URL persyaratan tertangkap sebagai parameter `{siswa}` dan tidak pernah mencapai `storeRequirement()`. Patch 2 memindahkan route statis `arsip-siswa/persyaratan` sebelum route dinamis `arsip-siswa/{siswa}`. Satu regression test tambahan kini memastikan router memilih nama route `admin.arsip-siswa.requirements.store`, bukan route parameter siswa. Sintaks kedua file terkait telah diperiksa dengan `php -l`; test harus dijalankan ulang pada XAMPP untuk mengonfirmasi perilaku runtime.

Angka full suite 227 passed, 5 failed, 2 skipped adalah hasil sebelum Patch 1. Full suite belum dijalankan ulang setelah Patch 1/Patch 2. Jangan menganggap angka tersebut sebagai hasil terbaru atau menyatakan suite keseluruhan lulus.

## 5. Migration baru

1. `2026_11_13_000001_create_student_document_archives.php`
2. `2026_11_13_000002_create_student_archive_requirements.php`
3. `2026_11_13_000003_create_student_archive_requirement_exemptions.php`

Migration bersifat aditif dan `down()` sengaja tidak menghapus data arsip. Migration pertama hanya memetakan dokumen lama yang jelas berkode `IJZ` ke kategori Ijazah. Dokumen berkode SKL/SKHUN tidak otomatis dipetakan ke SKLS. Dokumen Rapor lama yang tidak memiliki periode tengah/akhir juga tidak dipetakan otomatis. Pemetaan yang belum jelas perlu diperiksa Admin.

## 6. Pengujian otomatis yang ditambahkan/diperbarui

- `StudentDocumentArchiveTest`: arsip per siswa, status digital tanpa file, QR digital berulang dan pembatasan pemilik, kode verifikasi fisik, larangan metadata Rapor pada kategori lain, serta penolakan aturan kelengkapan duplikat.
- `PengajuanAlurTest`: alur Ajukan Layanan berbasis arsip, kepemilikan arsip, ketersediaan digital, duplikasi, QR fisik yang memerlukan Admin login, dan larangan menyelesaikan pengajuan fisik melalui endpoint status umum.
- `DocumentRepositoryTest` dan `PhysicalLoanTest`: menguji bahwa alur lama tidak lagi menerima transaksi baru sementara histori lama tetap dipertahankan.

Test tersebut telah diperiksa sintaks PHP, tetapi belum dieksekusi karena ekstensi PHP yang diperlukan tidak tersedia. Jangan mengklaim semua test baru lulus sebelum menjalankan suite pada XAMPP.

## 7. Checklist wajib pada Windows/XAMPP

Jalankan di database uji terpisah, bukan database produksi:

```powershell
php artisan migrate:status
php artisan migrate
php artisan route:list
php artisan test
php artisan test --filter=StudentDocumentArchiveTest
php artisan test --filter=PengajuanAlurTest
npm ci
npm audit
npm.cmd run build
```

Kemudian uji browser:

- Admin membuat aturan kelengkapan dan arsip per siswa.
- File tidak dapat diakses oleh siswa lain atau tanpa login.
- File pengganti tidak mengubah snapshot versi pada pengajuan yang sudah ada.
- Rapor semester 1/2 dan periode tengah/akhir.
- TKA, SKLS, Ijazah, Legalisir fisik dan digital.
- Hasil legalisir digital berbeda dari file asli.
- QR digital berulang, revoke, reissue, dan QR lama ditolak.
- QR fisik/kode manual, rotasi, expiry, konfirmasi satu kali.
- Dokumen Hilang/Rusak menolak pengajuan fisik.
- Kelengkapan arsip, pengecualian Tidak Berlaku, dan histori.
- Dashboard, laporan, notifikasi, tema Light/Dark, desktop/tablet/HP.

## 8. Batasan yang masih harus diselesaikan sebelum produksi

- Jalankan dan perbaiki seluruh test pada PHP XAMPP dengan `dom`, `mbstring`, `xmlwriter`, `pdo_sqlite`, dan `pdo_mysql`.
- Jalankan migration pada salinan database lama yang sudah dibackup, periksa `migrate:status`, foreign key, dan pemetaan IJZ.
- Uji concurrency pada MySQL/InnoDB, bukan hanya SQLite.
- Jalankan `npm ci` dan `npm.cmd run build` di Windows.
- Lakukan browser UAT lengkap, termasuk dark theme, HP, IDOR, file upload, QR, dan restore backup.
- Pemindaian malware/antivirus file upload belum dikonfigurasi oleh aplikasi; sekolah perlu menambahkan layanan scanner yang disetujui sebelum produksi bila diwajibkan kebijakan.
- Backup otomatis/off-site belum dikonfigurasi; lihat `PANDUAN_BACKUP_RESTORE_XAMPP.md`.

**Kesimpulan:** source telah dipatch dan lolos pemeriksaan sintaks, route, static checker, kompilasi Blade, serta pemeriksaan sintaks JavaScript pada lingkungan ini. Status yang benar adalah **siap untuk pengujian integrasi/UAT pada XAMPP**, bukan “selesai 100%” atau “siap produksi”.
