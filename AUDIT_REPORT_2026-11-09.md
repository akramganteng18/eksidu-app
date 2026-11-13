# Pembaruan status — 10 November 2026

Dokumen di bawah merupakan laporan historis audit 9 November. Status berikut memperbaruinya berdasarkan source terbaru dan laporan Windows/XAMPP pemilik:

- `composer install` berhasil; `npm audit` 0 vulnerabilities; Vite 6.4.5 build berhasil.
- Migration peminjaman fisik DONE; `route:list` 104; PHPUnit 223 passed / 781 assertions; `PhysicalLoanTest` 5 passed.
- Browser manual dilaporkan berhasil oleh pemilik.
- Source final diperiksa lagi: 118 file PHP lint, static checker lulus, JavaScript syntax lulus, dan 104 route terdaftar.
- Detail keterbatasan audit dan status masing-masing klaim ada pada `FINAL_TEST_REPORT.md`.

Hasil 9 November di bawah tetap dipertahankan sebagai riwayat, bukan status terkini.

---

# Laporan Audit dan Implementasi EKSiDU — 9 November 2026

## Basis audit
- Source: `eksidu-app.zip` yang diunggah pada percakapan ini.
- Dokumentasi yang dibaca: `PROJECT_HANDOVER.md`, `MASTER_PROMPT_AUDIT.md`, `TODO.md`, `CHANGELOG.md`, `README.md`, `DATABASE_SETUP.md`, `EKSiDU_PATCH_NOTE.txt`.
- Arsitektur tetap Laravel 10; tidak dibuat proyek baru.

## Kondisi source saat audit
Fitur lama yang sudah ada pada source: autentikasi multi-role, dashboard, master siswa/jenis dokumen, pengajuan akademik, notifikasi database, laporan XLSX/cetak, activity log, panduan media, profil/avatar, repository dokumen privat, peminjaman digital, QR bertoken, pengumuman, reset password, impor CSV, dan 2FA Admin.

Catatan: "sudah ada pada source" bukan berarti seluruhnya telah lulus runtime. Dokumentasi sumber mencatat beberapa pengujian lama berhasil di komputer pengguna, tetapi perubahan 9 November belum diuji ulang secara menyeluruh.

## Temuan dan tindakan
1. Peminjaman fisik belum memiliki inventaris/stok dan alur transaksi khusus. Ditambahkan tabel, model, controller, route, view, dan test terpisah.
2. Pembukaan QR repository dan unduhan via peminjaman belum semuanya masuk audit log. Ditambahkan pencatatan `accessed_at` dan activity log.
3. Endpoint QR dan unduhan repository diberi throttle 30 permintaan per menit.
4. Pemeriksa statis belum mengenali route/controller baru. Daftar referensinya diperbarui dan pemeriksa kembali lulus.
5. ZIP sumber memuat `.env`, dependency vendor/node_modules, file dokumen privat, sesi, dan cache runtime. Paket distribusi dibersihkan dari seluruh item tersebut; `.env.example` dipertahankan.

## Fitur peminjaman fisik yang ditambahkan
- Inventaris: kode unik, nama, deskripsi, stok total/tersedia, lokasi, aktif/nonaktif.
- Siswa: katalog stok tersedia, pengajuan, riwayat transaksi milik sendiri.
- Admin: persetujuan/penolakan, serah-terima, pencatatan tanggal pinjam dan jatuh tempo, pengembalian dan kondisi dokumen.
- Status: pending, approved, borrowed, returned, rejected; keterlambatan ditampilkan secara terhitung dari due date.
- Konsistensi: alokasi stok pada persetujuan dan pengembalian dalam transaksi DB dengan row lock.
- Audit: perubahan inventaris dan setiap tahap transaksi dicatat.

## Hasil pemeriksaan yang benar-benar dijalankan
- `php -l`: 118 file PHP pada app/database/routes/tests lulus.
- `node --check resources/js/app.js`: lulus.
- `python tools/verifikasi/static_check.py`: lulus, memindai 141 file PHP, 73 view, 18 komponen, dan 88 referensi route yang digunakan.
- ZIP hasil akhir diperiksa ulang untuk integritas arsip dan keberadaan `.env.example`.

## Pengujian yang belum dapat dijalankan
Lingkungan audit tidak memiliki `vendor/`, Composer, `pdo_mysql`, `pdo_sqlite`, `mbstring`, `dom`, `xmlwriter`, atau `zip`. `node_modules/` juga tidak disertakan dalam workspace audit. Karena itu:
- PHPUnit tidak dijalankan.
- `php artisan migrate` dan migration MySQL tidak dijalankan.
- `php artisan route:list`, `view:cache`, `route:cache` tidak dijalankan.
- `npm run build` tidak dijalankan.
- Browser E2E, concurrency test MySQL, dan pengujian visual desktop/tablet/mobile belum dilakukan.

## Risiko / pekerjaan tersisa
- Wajib jalankan migration additive pada salinan/backup database terlebih dahulu.
- Jalankan `PhysicalLoanTest`, lalu seluruh PHPUnit di XAMPP.
- Uji concurrency dua admin pada stok terakhir dengan MySQL.
- Audit manual seluruh endpoint IDOR dan download, serta uji QR dicabut/kedaluwarsa.
- Uji reset password dan email dengan mail log/SMTP; uji 2FA dan recovery code.
- Uji impor CSV, pengumuman, laporan, panduan media, dan kedua tema di browser.
- Build Vite di Windows dengan `npm ci` dan `npm.cmd run build`.
- PDF export sungguhan, XLSX import, E2E otomatis, login-failed log/sesi aktif, self-host QR scanner, dan thumbnail media tetap merupakan pekerjaan tersisa sebagaimana TODO.

## Petunjuk aman sebelum update XAMPP
1. Backup database MySQL dan folder `storage/app`.
2. Ekstrak ZIP di luar folder produksi terlebih dahulu atau buat salinan folder proyek.
3. Pastikan `.env` lama disalin sendiri ke folder kerja dan tidak diganti oleh `.env.example`.
4. Jalankan `composer install` bila `vendor` belum tersedia.
5. Jalankan `npm ci` dan `npm.cmd run build` bila ingin membangun ulang aset.
6. Jalankan `php artisan migrate:status`, lalu `php artisan migrate` setelah backup.
7. Jalankan `php artisan test --filter=PhysicalLoanTest`, lalu `php artisan test`.
8. Jangan jalankan `migrate:fresh`, `db:wipe`, atau rollback destruktif pada database berisi data.

**Kesimpulan:** source telah diperluas dan pemeriksaan statis lulus. Aplikasi belum boleh disebut final/siap produksi sampai pengujian runtime dan browser pada XAMPP berhasil.


## Hasil verifikasi tambahan dari XAMPP pengguna — 9 November 2026

Pengguna menjalankan dependency installation, build, migration, route listing, dan PHPUnit di Windows/XAMPP. Bukti yang dilaporkan:

- `composer install`: selesai dengan package discovery.
- `npm ci`: selesai, tetapi melaporkan 4 vulnerability (2 moderate, 2 high) dan peringatan install-script `esbuild`; advisory detail belum disediakan.
- `npm.cmd run build`: berhasil dengan Vite 5.4.21.
- Migration `2026_11_09_000003_create_physical_loans_tables`: berhasil dijalankan.
- `php artisan route:list`: berhasil dan menunjukkan 104 route.
- `php artisan test`: 222 passed, 1 failed, 777 assertions. Kegagalan tunggal hanya pada pembandingan `due_at`: SQLite menyimpan cast date dengan waktu tengah malam, sedangkan assertion awal mengharapkan string tanggal saja. Test v4 diperbaiki untuk membandingkan tanggal melalui cast model. Hasil setelah patch masih menunggu eksekusi ulang.

Ini memperbarui bukti runtime sebelumnya, tetapi bukan bukti bahwa semua alur browser, keamanan, concurrency MySQL, atau produksi telah teruji.
