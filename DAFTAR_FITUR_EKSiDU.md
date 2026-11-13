# Daftar Fitur EKSiDU — 13 November 2026

Daftar ini memisahkan fitur yang tersedia pada source dari bukti pengujian. Lihat `FINAL_TEST_REPORT.md` untuk status verifikasi.

## Siswa

- Login, logout, profil, avatar privat, pengaturan password, notifikasi.
- Dashboard personal dan riwayat pengajuan milik sendiri.
- Satu menu **Ajukan Layanan**: Rapor, TKA, SKLS, Ijazah, Legalisir.
- Pemilihan Fisik/Digital berdasarkan arsip milik siswa dan status ketersediaan terbaru.
- Rapor memiliki metadata tahun ajaran, semester 1/2, dan periode tengah/akhir.
- Detail tiket, status, histori, notifikasi.
- Dokumen digital yang disetujui dapat dipreview/diunduh berulang kali selama akses aktif.
- QR fisik dan kode verifikasi ditampilkan setelah persetujuan.

## Admin/TU

- Dashboard, master siswa, master jenis dokumen, pengumuman, notifikasi, activity log.
- Satu menu **Arsip Dokumen Siswa** dengan pencarian siswa dan arsip per siswa.
- Upload file privat, edit metadata, ganti versi file tanpa menimpa versi lama, status digital dan fisik terpisah, lokasi fisik, catatan, histori.
- Status fisik: belum tercatat, tersedia, tidak tersedia, hilang, rusak.
- Status digital: belum diunggah, tersedia, tidak tersedia, diarsipkan. Status tersedia mensyaratkan file yang benar-benar ada.
- Aturan kelengkapan yang dapat ditentukan per kelas, kategori, tahun ajaran, semester, dan periode.
- Pengecualian **Tidak Berlaku** per siswa dengan alasan dan histori.
- Soft delete arsip ditolak bila ada pengajuan aktif; file dan histori tidak dihapus permanen.
- Pengajuan masuk, validasi status, alasan penolakan, QR fisik, kode manual, QR digital, revoke/reissue.
- Hasil legalisir digital disimpan terpisah dari dokumen asli.
- Laporan dengan filter tanggal, status, layanan, kategori, bentuk, tahun ajaran, semester, dan periode; ekspor XLSX/CSV fallback dan cetak.

## Keamanan dan privasi

- Otorisasi role Admin/Siswa dan pemeriksaan kepemilikan arsip/pengajuan.
- File dokumen disimpan pada disk privat, tidak di folder publik.
- Token QR digital di-hash dan ciphertext; QR bertanda tangan, berulang, dapat dicabut, reissue mengganti token.
- Kode fisik di-hash, expiry, rotasi, dan dihapus setelah konfirmasi.
- Rate limiting pada route akses/scan, CSRF pada form, activity log, cache no-store dan header keamanan pada preview.
- Validasi upload PDF/JPG/PNG dan batas ukuran; antivirus scanner eksternal belum dikonfigurasi.

## Modul historis

Repository dokumen dan peminjaman fisik lama tidak menjadi jalur transaksi baru. Tabel, histori, dan sebagian akses QR/download historis tetap dipertahankan untuk mencegah kehilangan data. Jangan menghapus tabel lama tanpa persetujuan dan pemetaan data.

## Fitur lain yang dipertahankan

- 2FA Admin TOTP dan recovery codes.
- Reset password Laravel Password Broker.
- Import siswa CSV dengan pratinjau.
- Panduan multimedia dan pengumuman.
- Tema Light/Dark dan layout responsif.

## Belum dikonfirmasi / pekerjaan operasional

- PHPUnit penuh setelah patch, migration MySQL, Vite build di Windows, browser UAT, concurrency MySQL, dan backup/restore harus dijalankan.
- Backup otomatis/off-site dan antivirus upload belum dikonfigurasi.
