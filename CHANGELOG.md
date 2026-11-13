# CHANGELOG — EKSiDU

## 13 November 2026 — Perbaikan berdasarkan hasil uji XAMPP pemilik

- Memperbaiki respons halaman preview QR digital agar dikembalikan sebagai HTTP response sebelum header keamanan ditambahkan (mengatasi HTTP 500 pada preview).
- Menyamakan state machine status `approved` dengan matriks transisi yang diharapkan: penyelesaian fisik melalui konfirmasi serah-terima, bukan transisi langsung ke `rejected`.
- Menormalkan pencocokan dimensi aturan kelengkapan nullable agar pemeriksaan duplikasi konsisten lintas MySQL/MariaDB/SQLite.
- Memperbarui regresi unggah berkas pendukung agar menggunakan kontrak Ajukan Layanan terpadu, bukan payload peminjaman lama.
- Mengganti konstanta PDO SSL CA yang deprecated pada PHP 8.5 dengan `Pdo\Mysql::ATTR_SSL_CA`, dengan fallback untuk PHP 8.1–8.4.
- Perbaikan ini telah lolos pemeriksaan sintaks pada 141 file PHP dan pemeriksaan sintaks JavaScript; PHPUnit harus dijalankan ulang pada XAMPP pemilik untuk konfirmasi setelah patch.

## 13 November 2026 — Alur arsip siswa dan pengajuan terpadu

- Menambahkan arsip dokumen per siswa, histori perubahan, aturan kelengkapan dinamis, dan pengecualian Tidak Berlaku per siswa.
- Menambahkan satu alur Ajukan Layanan untuk Rapor, TKA, SKLS, Ijazah, dan Legalisir dengan pilihan bentuk Fisik/Digital berdasarkan ketersediaan arsip milik siswa.
- Menambahkan snapshot versi dokumen pada pengajuan agar file pengganti tidak mengubah versi yang sudah diajukan.
- Menambahkan upload hasil legalisir digital sebagai dokumen terpisah dari dokumen asli.
- Menambahkan token QR digital berulang yang disimpan dalam hash + ciphertext, revoke/reissue yang membatalkan QR lama, serta kode verifikasi fisik ter-hash, expiry, rotasi, dan konfirmasi satu kali.
- Mengarahkan menu dan controller transaksi lama ke alur baru; data tabel dan histori lama dipertahankan.
- Memperluas dashboard dan laporan dengan metadata arsip/kategori/bentuk/tahun ajaran/semester/periode.
- Menambahkan panduan backup/restore XAMPP.
- Memperketat validasi metadata kategori, mencegah aturan persyaratan kelengkapan aktif duplikat, dan memeriksa arsip soft-delete saat validasi identitas metadata.
- Memperbaiki pagination Dokumen Saya agar hanya menghitung file yang benar-benar dapat diakses.
- Mencegah pengajuan fisik baru diselesaikan lewat endpoint status umum; kode/token dibersihkan saat penolakan dan serah-terima manual.
- Pemeriksaan sesi: syntax lint, static checker, route list (123 route), dan JS syntax lulus; PHPUnit/migration MySQL/Vite build/browser UAT belum dapat diulang pada lingkungan audit. Rincian ada di `FINAL_TEST_REPORT.md`.

## 10–12 November 2026 — Riwayat finalisasi sebelumnya

- Paket distribusi dibersihkan dari `.env`, dependency terpasang, file privat dan runtime.
- Hasil pengujian Windows/XAMPP pada waktu tersebut dicatat dalam laporan historis. Angka test dan route historis tidak dianggap bukti kelulusan patch 13 November.
