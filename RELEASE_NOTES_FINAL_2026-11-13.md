# EKSiDU — Release Notes 13 November 2026

## Perubahan utama

- Alur siswa dipusatkan pada Ajukan Layanan (Rapor, TKA, SKLS, Ijazah, Legalisir) dengan pilihan Fisik/Digital dari arsip milik siswa.
- Admin mengelola Arsip Dokumen Siswa, status digital/fisik, file privat, metadata, versi, histori, standar kelengkapan, dan pengecualian Tidak Berlaku.
- Hasil legalisir digital disimpan sebagai file terpisah dari dokumen asli.
- Pengajuan menyimpan snapshot versi dokumen.
- QR digital berulang menggunakan token acak yang hash + terenkripsi; revoke/reissue membatalkan QR lama.
- QR fisik dan kode manual memerlukan Admin login; kode manual berlaku 14 hari, dapat dirotasi, dan invalid setelah konfirmasi.
- Perubahan arsip fisik menjadi tidak tersedia membatalkan pengajuan fisik yang belum diserahkan.
- Menu transaksi lama dialihkan ke alur baru; tabel/histori lama tetap dipertahankan.
- Dashboard dan laporan diperbarui.
- Validasi server membatasi metadata semester/periode hanya untuk kategori Rapor dan menolak aturan kelengkapan duplikat yang aktif.
- Pemeriksaan duplikasi metadata juga mempertimbangkan arsip yang sudah soft-delete agar tidak menimbulkan benturan unique key.
- Daftar Dokumen Saya memfilter kelayakan file sebelum pagination agar total dan halaman sesuai dokumen yang benar-benar dapat dibuka.
- Pengajuan fisik baru tidak dapat langsung diubah ke Selesai melalui endpoint status umum; penyelesaian harus melalui konfirmasi serah-terima. Penolakan juga membersihkan token/kode akses aktif.

## Migration baru

- `2026_11_13_000001_create_student_document_archives.php`
- `2026_11_13_000002_create_student_archive_requirements.php`
- `2026_11_13_000003_create_student_archive_requirement_exemptions.php`

Pemetaan otomatis legacy hanya untuk kode `IJZ`. SKL/SKHUN tidak otomatis disamakan dengan SKLS; Rapor lama tanpa periode tidak dipetakan otomatis.

## Bukti pemeriksaan sesi

- 152 file PHP lulus `php -l`.
- 78 Blade view berhasil dikompilasi dan hasil PHP-nya lolos lint.
- Static checker: 156 PHP, 78 view, 18 komponen, 105 referensi route; tidak ada temuan.
- Test regresi tambahan diperiksa sintaksnya untuk aturan persyaratan duplikat, metadata non-Rapor, dan larangan menyelesaikan pengajuan fisik lewat endpoint status umum.
- `php artisan route:list --json`: 123 route.
- 4 file JavaScript lulus `node --check`.
- `npm audit --offline --audit-level=high`: 0 vulnerabilities dilaporkan.

PHPUnit, migration MySQL, Vite build, Composer validation, dan browser UAT belum dapat dijalankan ulang pada lingkungan audit. Statusnya belum terverifikasi; lihat `FINAL_TEST_REPORT.md`.

## Patch 2 — 13 November 2026

- Memperbaiki urutan route tetap `POST arsip-siswa/persyaratan` agar didaftarkan sebelum route dinamis `POST arsip-siswa/{siswa}`.
- Mencegah endpoint persyaratan ditangkap sebagai parameter siswa; permintaan sekarang diarahkan ke `storeRequirement()` sehingga pemeriksaan duplikasi dapat berjalan.
- Perubahan tidak menambah atau mengubah migration dan tidak mengubah database.
- `php -l routes/web.php` lulus pada paket patch. Pengujian `StudentDocumentArchiveTest` setelah pemasangan Patch 2 menunggu verifikasi ulang di Windows/XAMPP.
