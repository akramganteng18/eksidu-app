# MASTER PROMPT AUDIT — STATUS EKSiDU

## Source terbaru

Source kerja ini berasal dari ZIP `eksidu-app(1).zip` yang dilampirkan pemilik pada sesi 13 November 2026. Pekerjaan adalah kelanjutan proyek EKSiDU lama. Jangan membuat proyek baru, jangan menghapus data historis, dan jangan mengganti APP_KEY.

## Arah perubahan yang telah dipatch

- Satu alur siswa: **Ajukan Layanan**.
- Satu pengelolaan Admin: **Arsip Dokumen Siswa**.
- Kategori Rapor, TKA, SKLS, Ijazah, dan Legalisir.
- Arsip terhubung dengan akun siswa, status fisik/digital terpisah, file privat, versi, histori, standar kelengkapan dan pengecualian Tidak Berlaku.
- QR digital berulang dan dapat dicabut/diterbitkan ulang; QR fisik dan kode manual memerlukan petugas Admin login.
- Hasil legalisir digital adalah file terpisah dari dokumen asli.
- Modul lama tidak menerima transaksi baru melalui menu lama; tabel dan histori tetap dipertahankan.

## Migration baru

1. `2026_11_13_000001_create_student_document_archives.php`
2. `2026_11_13_000002_create_student_archive_requirements.php`
3. `2026_11_13_000003_create_student_archive_requirement_exemptions.php`

Migration bersifat aditif. Hanya dokumen lama kode `IJZ` dipetakan otomatis ke Ijazah. SKL/SKHUN tidak dipetakan otomatis ke SKLS. Rapor lama tanpa periode tidak dipetakan otomatis.

## Hasil pemeriksaan sesi

- PHP syntax lint: 152 file lulus.
- Static checker: 156 PHP, 78 view, 18 komponen, 105 route references; tidak ada temuan.
- `php artisan route:list --json`: 123 route.
- Blade compiler + PHP lint: 78 view lulus kompilasi dan syntax lint.
- JavaScript syntax: 4 file lulus.
- `npm audit --offline`: 0 vulnerabilities dilaporkan.
- PHPUnit: tidak dapat dimulai karena ekstensi PHP `dom`, `mbstring`, `xmlwriter` tidak tersedia.
- Migration MySQL: tidak dijalankan karena driver PDO tidak tersedia.
- Vite build: tidak dapat dijalankan karena optional Rollup binary Linux tidak tersedia pada `node_modules` dari Windows.
- Composer validate: tidak dijalankan karena Composer CLI tidak tersedia.
- Browser UAT: belum dijalankan pada sesi ini.

Lihat `FINAL_TEST_REPORT.md` dan `PROJECT_HANDOVER.md`.

## Aturan mutlak

- Jangan jalankan `migrate:fresh` / `db:wipe` pada data sekolah.
- Jangan mengganti `.env`, APP_KEY, atau menghapus storage privat.
- Jangan memasukkan `.env`, kredensial, data siswa, file privat, log, session, atau cache ke ZIP distribusi.
- Jangan menganggap test/migration/build/browser lulus tanpa bukti aktual.
- Jalankan test lengkap, migration di database uji, Vite build, browser UAT, dan backup/restore sebelum produksi.
