# HISTORICAL UAT SIGN-OFF — EKSiDU (12 November 2026)

**Tanggal laporan historis:** 12 November 2026

> **PENTING — TIDAK BERLAKU UNTUK PATCH 13 NOVEMBER 2026.** Dokumen ini merekam hasil UAT pemilik sebelum perubahan alur arsip/pengajuan terpadu. Angka 231 test/817 assertions dan 105 route bukan bukti bahwa source patch 13 November telah lulus. Gunakan `FINAL_TEST_REPORT.md` sebagai status pengujian terkini.  
**Aplikasi:** EKSiDU — Ekosistem Digital Sekolah  
**Organisasi pengguna:** SMK Negeri 5 Surakarta  
**Lingkungan uji:** Windows / XAMPP / browser / perangkat HP (sesuai laporan pemilik).

## 1. Pernyataan hasil

Pemilik proyek telah mengeksekusi dan menandai seluruh checklist UAT di bawah sebagai berhasil. Pada snapshot 12 November, pemilik melaporkan seluruh fungsi yang tercakup dalam skenario penerimaan ini **LULUS UAT**. Hasil ini bukan sertifikasi keamanan independen dan bukan bukti bahwa server produksi sudah dideploy.

## 2. Checklist penerimaan

| No. | Skenario | Hasil |
|---:|---|---|
| 1 | Login Admin dan Siswa; dashboard berbeda sesuai role | LULUS |
| 2 | Sidebar Admin: Inventaris Fisik dan Peminjaman Fisik | LULUS |
| 3 | Upload, pratinjau, dan download dokumen privat | LULUS |
| 4 | QR pengajuan dan Repository dipindai menggunakan HP | LULUS |
| 5 | Peminjaman digital sampai selesai | LULUS |
| 6 | Dua pengajuan peminjaman fisik bersamaan ketika stok terbatas | LULUS |
| 7 | Persetujuan, serah-terima, pengembalian, dan perubahan stok | LULUS |
| 8 | Notifikasi, pengumuman, reset password, dan 2FA | LULUS |
| 9 | Import siswa dan export laporan | LULUS |
| 10 | Desktop, tablet, mobile, Light Theme, dan Dark Theme | LULUS |

## 3. Bukti teknis pendukung dari mesin pemilik

| Pemeriksaan | Hasil yang dilaporkan |
|---|---|
| Composer | `composer install` berhasil |
| Database | 17 migration berstatus `Ran` |
| Route | 105 route |
| PHPUnit | 231 tests passed; 817 assertions; 0 gagal |
| Frontend install | `npm ci` berhasil; 94 paket ditambahkan/diaudit |
| Dependency audit | `npm audit`: 0 vulnerabilities |
| Production build | `npm.cmd run build`: Vite 6.4.5 berhasil |

Peringatan `allowScripts` untuk `esbuild@0.25.12` muncul pada install; build pada mesin pemilik tetap sukses. Tinjau kebijakan install script saat update dependency berikutnya.

## 4. Keputusan penerimaan

- Fitur inti dalam skenario UAT diterima berdasarkan hasil uji pemilik.
- Tidak ada kebutuhan untuk mengulang implementasi fitur yang sudah lulus tanpa laporan bug baru.
- Pernyataan penerimaan ini hanya berlaku untuk snapshot 12 November, bukan patch terpadu 13 November.
- Data database dan folder berkas privat bukan bagian dari ZIP distribusi; data tersebut harus tetap dipertahankan/di-backup terpisah.

## 5. Checklist operasional sebelum go-live

Item ini tidak membatalkan kelulusan UAT aplikasi, tetapi harus diselesaikan pada lingkungan server yang akan digunakan:

- [ ] Tetapkan server/domain produksi dan aktifkan HTTPS.
- [ ] Set `APP_URL` dan `EKSIDU_PUBLIC_URL` ke domain kanonis yang dapat dijangkau perangkat HP.
- [ ] Pastikan `APP_DEBUG=false`; simpan `APP_KEY` dan secret secara aman. Jangan mengganti `APP_KEY` pada instalasi lama.
- [ ] Pastikan permission `storage` dan `bootstrap/cache` sesuai dan file privat tidak berada di direktori publik.
- [ ] Konfigurasikan SMTP jika pengiriman email dibutuhkan dan uji dari server target.
- [ ] Buat backup database serta `storage/app` dan uji restore di staging/lingkungan terpisah.
- [ ] Aktifkan 2FA untuk Admin operasional dan simpan recovery code dengan aman.
- [ ] Rotasi QR Repository aktif setelah perubahan host/domain.
- [ ] Tetapkan jadwal pembaruan dependency, monitoring log, dan pemantauan kapasitas disk.

## 6. Batasan dan backlog non-blocking

Fitur tambahan yang belum tersedia penuh: ekspor PDF server-side khusus (saat ini dapat melalui cetak browser), impor XLSX (saat ini CSV), integrasi validasi NIS eksternal, thumbnail media panduan, self-host QR scanner, daftar sesi aktif/log login gagal khusus, queue notifikasi skala besar, dan browser E2E otomatis. Ini merupakan pengembangan lanjutan, bukan kegagalan pada skenario UAT di atas.

**Kesimpulan historis:** EKSiDU pada snapshot 12 November dilaporkan lulus skenario UAT yang dijalankan pemilik. Status patch 13 November dijelaskan terpisah pada `FINAL_TEST_REPORT.md`. Pernyataan “siap produksi” berlaku setelah checklist operasional server target dipenuhi; tidak ada klaim sertifikasi keamanan formal.
