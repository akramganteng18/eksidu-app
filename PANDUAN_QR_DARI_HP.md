# Panduan QR EKSiDU dari HP — Patch 13 November 2026

Panduan ini mencakup dua alur berbeda: QR Digital untuk membuka file privat dan QR Fisik untuk verifikasi serah-terima oleh Admin/TU.

## 1. Persiapan alamat publik

- Jika diuji dari HP pada Wi-Fi yang sama, atur `EKSIDU_PUBLIC_URL` ke alamat LAN PC, misalnya `http://192.168.1.20:8000` (ganti dengan IP PC yang benar).
- Pastikan Windows Firewall mengizinkan port yang digunakan.
- Untuk produksi, gunakan HTTPS/domain resmi; jangan membuka aplikasi ke internet tanpa konfigurasi keamanan sekolah.
- Setelah mengubah `.env`, jalankan `php artisan config:clear`.

## 2. QR dokumen digital

1. Admin memastikan arsip digital siswa tersedia dan file berada pada disk privat.
2. Siswa mengajukan layanan dengan bentuk Digital.
3. Admin memproses dan menyetujui pengajuan.
4. Siswa membuka detail tiket dari akun sendiri; QR digital muncul.
5. Pindai QR dari HP. Pengguna harus login; hanya pemilik pengajuan atau Admin yang berwenang dapat membuka dokumen.
6. Dokumen dapat dibuka/diunduh berulang kali selama akses aktif.
7. Admin dapat mencabut akses. QR lama langsung tidak berlaku. Jika diperlukan, Admin menerbitkan ulang QR dengan token baru.

QR digital menggunakan token acak, hash token, ciphertext di database, URL bertanda tangan, dan pemeriksaan kepemilikan. Jangan membagikan screenshot QR atau URL bertoken ke pihak lain.

## 3. QR dan kode verifikasi fisik

1. Siswa mengajukan bentuk Fisik dari arsip yang berstatus Tersedia.
2. Admin menyetujui pengajuan; sistem membuat kode verifikasi acak yang berlaku 14 hari.
3. Siswa membuka detail tiket dan menunjukkan QR atau kode manual kepada petugas.
4. Petugas harus login sebagai Admin/TU sebelum halaman verifikasi menampilkan data pengajuan.
5. Jika kamera tidak tersedia, Admin membuka menu Scan QR dan memasukkan kode verifikasi atau kode tiket.
6. Petugas mengonfirmasi serah-terima. Konfirmasi hanya berhasil satu kali; hash dan kode terenkripsi dihapus setelah selesai.
7. Admin dapat merotasi kode. Kode lama langsung tidak berlaku.

## 4. Jika QR tidak terbuka

- Pastikan `EKSIDU_PUBLIC_URL` menunjuk ke host yang dapat dijangkau HP, bukan `localhost` pada PC.
- Pastikan URL belum kedaluwarsa. QR digital dapat dibuat ulang dari detail tiket; QR fisik dapat diterbitkan ulang melalui Admin.
- Pastikan siswa login dengan akun pemilik pengajuan.
- Pastikan file digital dan status arsip masih aktif.
- Pastikan PC dan HP berada pada jaringan yang dapat saling terhubung.
- Periksa `storage/logs/laravel.log`; hapus token dan data pribadi sebelum mengirim log ke pihak lain.

## 5. Larangan

- Jangan memotret/menyebarkan QR dokumen digital milik siswa lain.
- Jangan menonaktifkan pemeriksaan login atau signed URL untuk mempermudah scan.
- Jangan memindahkan file privat ke `public`.
- Jangan menggunakan QR fisik sebagai pengganti login petugas.

## 6. Pengujian penerimaan

- [ ] QR digital dapat dibuka beberapa kali oleh pemilik.
- [ ] Akun siswa lain menerima 403/404.
- [ ] QR digital yang dicabut tidak dapat dipakai.
- [ ] QR digital baru setelah reissue berfungsi dan QR lama tetap tidak berlaku.
- [ ] QR fisik meminta Admin login.
- [ ] Kode manual yang salah/kedaluwarsa ditolak.
- [ ] Rotasi kode membatalkan kode lama.
- [ ] Konfirmasi serah-terima kedua kali ditolak.
- [ ] QR tidak membuka file jika file versi sudah tidak tersedia.
