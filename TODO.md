# TODO — EKSiDU

Status ini mengikuti source patch 13 November 2026. Item yang belum diuji tidak boleh ditandai selesai hanya karena source tersedia.

## Wajib sebelum rilis

- [ ] Jalankan migration baru pada salinan database lama dan periksa `migrate:status`, foreign key, unique identity, dan pemetaan IJZ.
- [ ] Jalankan `php artisan test` penuh di Windows/XAMPP dengan ekstensi `dom`, `mbstring`, `xmlwriter`, `pdo_sqlite` aktif.
- [ ] Perbaiki seluruh test gagal; jangan menghapus assertion untuk membuat suite hijau.
- [ ] Jalankan `npm ci`, `npm audit`, dan `npm.cmd run build` di Windows.
- [ ] Uji alur Ajukan Layanan untuk Rapor (semester 1/2, tengah/akhir), TKA, SKLS, Ijazah, Legalisir fisik dan digital.
- [ ] Uji hasil legalisir terpisah dari dokumen asli, preview, download, versi, dan QR.
- [ ] Uji QR digital berulang, revoke, reissue, token lama, expiry, akses lintas akun, dan file hilang.
- [ ] Uji QR fisik dan kode manual, rotasi, expiry, serah-terima satu kali, dan akun Admin wajib login.
- [ ] Uji standar kelengkapan, pengecualian Tidak Berlaku, status Hilang/Rusak, histori, dan soft delete arsip.
- [ ] Uji concurrency pengajuan dan perubahan arsip pada MySQL/InnoDB.
- [ ] Uji IDOR, upload file, private disk, headers, authorization Admin/Siswa.
- [ ] Browser UAT desktop/tablet/HP dan Light/Dark theme.
- [ ] Uji backup dan restore database + storage pada lingkungan terpisah.

## Penguatan operasional

- [ ] Konfigurasikan malware scanner untuk upload file jika diwajibkan kebijakan sekolah; saat ini belum terintegrasi.
- [ ] Atur backup otomatis terenkripsi/off-site, retensi, monitoring, dan uji restore berkala.
- [ ] Konfigurasikan HTTPS/domain dan SMTP produksi jika notifikasi email digunakan.
- [ ] Tinjau kebijakan retensi file dan histori bersama pihak sekolah.

## Catatan transisi

- Tabel repository dan peminjaman fisik lama dipertahankan sebagai histori.
- Hanya kode dokumen `IJZ` yang dipetakan otomatis ke arsip Ijazah. SKL/SKHUN tidak otomatis disamakan dengan SKLS; Rapor lama tanpa periode juga perlu pemetaan manual.
- Jangan menjalankan `migrate:fresh` atau `db:wipe` pada database sekolah.
