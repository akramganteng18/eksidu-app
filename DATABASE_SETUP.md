# DATABASE_SETUP — EKSiDU

**Snapshot:** 13 November 2026. Dokumen ini menjelaskan skema yang dipakai oleh alur arsip dan pengajuan terpadu. Database sekolah lama harus di-backup sebelum migration.

## 1. Database instalasi baru

Gunakan MySQL/MariaDB dengan charset `utf8mb4` dan collation `utf8mb4_unicode_ci`.

```sql
CREATE DATABASE eksidu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'eksidu'@'localhost' IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON eksidu.* TO 'eksidu'@'localhost';
```

Isi `.env` sesuai kredensial MySQL setempat. Jangan memakai password contoh pada server sekolah.

## 2. Tabel inti

| Tabel | Fungsi | Catatan |
|---|---|---|
| `users` | Akun Admin/Siswa | relasi arsip berdasarkan `user_id` |
| `document_types` | Master jenis dokumen | metadata master lama tetap dipertahankan |
| `documents` | File privat dan metadata dokumen/versi | file tidak disimpan di `public` |
| `student_document_archives` | Arsip per siswa/kategori/metadata akademik | status digital/fisik, lokasi, versi, identity key, soft delete |
| `student_document_archive_histories` | Histori perubahan status, versi, dan metadata | histori tetap ada saat arsip soft delete |
| `student_archive_requirements` | Standar kelengkapan arsip | dinamis per kelas/kategori/tahun ajaran/semester/periode |
| `student_archive_requirement_exemptions` | Pengecualian Tidak Berlaku per siswa | alasan tersimpan, soft delete |
| `pengajuans` | Tiket layanan siswa | archive id, bentuk, kategori, semester/periode, snapshot versi, token QR |
| `pengajuan_riwayats` | Histori status pengajuan | perubahan status dan catatan |
| `ticket_counters` | Nomor tiket per tahun | menggunakan transaksi/row lock |
| `activity_logs` | Audit aktivitas | catatan Admin/Siswa |
| `notifications` | Notifikasi Laravel | database dan email bila dikonfigurasi |
| `document_loans` | Transaksi repository lama | data historis/akses lama dipertahankan |
| `physical_inventory_items`, `physical_loans` | Inventaris/peminjaman fisik lama | bukan alur transaksi baru |
| `panduan_media`, `pengumumans` | Panduan dan pengumuman | modul pendukung |

## 3. Migration arsip terpadu (13 November 2026)

Setelah migration sebelumnya, migration baru adalah:

1. `2026_11_13_000001_create_student_document_archives.php`
2. `2026_11_13_000002_create_student_archive_requirements.php`
3. `2026_11_13_000003_create_student_archive_requirement_exemptions.php`

Migration pertama menambahkan tabel arsip dan histori serta kolom baru pada `pengajuans`: `archive_id`, `document_version_id`, `result_document_id`, `kategori_layanan`, `bentuk_dokumen`, `academic_year`, `semester_akademik`, `periode_rapor`, token/hash QR digital, dan kode/hash/expiry verifikasi fisik.

Migration bersifat aditif. `down()` tidak menghapus arsip/histori untuk menghindari kehilangan data sekolah. Hanya dokumen lama berkode `IJZ` yang dipetakan otomatis ke kategori Ijazah, bila file dan pemilik siswa dapat dikenali. SKL/SKHUN tidak disamakan otomatis dengan SKLS. Rapor lama tanpa periode tengah/akhir tidak dipetakan otomatis.

## 4. Menjalankan migration

```powershell
php artisan migrate:status
php artisan migrate
```

Pada instalasi baru, jalankan `php artisan db:seed` setelah migration untuk data master/Admin awal. Pada update database sekolah lama, jangan menjalankan seeder kecuali telah ditinjau dan memang diperlukan.

**Jangan pernah** menjalankan `php artisan migrate:fresh` atau `php artisan db:wipe` pada database yang berisi data sekolah.

## 5. Database pengujian

`phpunit.xml` memakai SQLite in-memory. PHP CLI harus memiliki `pdo_sqlite`. `RefreshDatabase` akan mengosongkan database test; jangan mengarahkan test ke database MySQL produksi.

Untuk pengujian manual browser, buat database terpisah seperti `eksidu_uji` melalui phpMyAdmin. Jangan gunakan database sekolah aktif.

## 6. Backup dan pemulihan

Backup database dan folder `storage/app/berkas` secara bersamaan. Database menyimpan path/metadata; file fisik berada pada disk privat. Simpan `.env`/APP_KEY terpisah dan rahasiakan. Panduan perintah Windows/XAMPP ada di `PANDUAN_BACKUP_RESTORE_XAMPP.md`.

## 7. Catatan transisi

- Tabel `documents`, `document_loans`, `physical_inventory_items`, dan `physical_loans` tidak dihapus. Menu lama diarahkan ke Ajukan Layanan/Arsip Dokumen Siswa dan transaksi baru lewat controller lama dibatasi.
- Snapshot versi pada `pengajuans.document_version_id` menjaga pengajuan lama tetap menunjuk file yang digunakan saat pengajuan dibuat. Hasil legalisir digital menggunakan `result_document_id` terpisah.
- Jangan menghapus file lama yang masih direferensikan pengajuan/riwayat.
- Jangan mengganti APP_KEY pada instalasi lama karena token digital dan kode fisik disimpan terenkripsi.

## 8. Batas operasional

Backup otomatis/off-site dan malware scanner untuk upload belum dikonfigurasi pada source ini. Atur sesuai kebijakan sekolah sebelum produksi. Pengujian migration MySQL harus dilakukan pada salinan database terlebih dahulu.
