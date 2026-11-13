# Panduan Backup dan Pemulihan EKSiDU — Windows/XAMPP

Panduan ini memisahkan backup database dan storage privat. Keduanya harus berasal dari waktu yang berdekatan agar relasi metadata dan file tetap cocok.

## 1. Lokasi data penting

- Database MySQL/MariaDB: seluruh tabel EKSiDU, termasuk `student_document_archives`, `student_document_archive_histories`, `student_archive_requirements`, `student_archive_requirement_exemptions`, `pengajuans`, dan tabel histori lama.
- File privat: `storage/app/berkas` (atau disk privat yang ditentukan oleh `EKSIDU_DISK_BERKAS`).
- Konfigurasi: `.env` dan `APP_KEY`. Jangan mengganti APP_KEY saat memulihkan instalasi yang sudah berjalan karena data terenkripsi dapat tidak terbaca.

## 2. Backup database melalui PowerShell

Jalankan dari PowerShell. Sesuaikan nama database, username, dan lokasi MySQL XAMPP.

```powershell
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "D:\Backup-EKSiDU\$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null
& 'C:\xampp\mysql\bin\mysqldump.exe' -u root -p --single-transaction --routines --triggers --result-file="$backup\eksidu.sql" eksidu
```

Jika database memakai user khusus, ganti `root`. Jangan menaruh password di command line atau file batch yang dibagikan. Untuk database besar, gunakan dump tool yang sesuai dan periksa exit code serta ukuran file.

## 3. Backup storage privat dan konfigurasi

```powershell
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "D:\Backup-EKSiDU\$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null
Compress-Archive -Path '.\storage\app\berkas' -DestinationPath "$backup\storage-berkas.zip"
Copy-Item '.\.env' "$backup\.env.backup"
```

Simpan folder backup di luar `C:\xampp\htdocs`, batasi izin aksesnya, dan enkripsi media backup sesuai kebijakan sekolah. Jangan memasukkan backup ke `public` atau repository Git.

## 4. Catatan konsistensi

- Hentikan sementara upload/perubahan arsip saat backup database dan storage agar keduanya konsisten.
- Catat waktu backup, nama database, versi aplikasi, dan lokasi file.
- Jangan menghapus file lama yang masih direferensikan `documents` atau `pengajuans`.
- Jangan menganggap backup berhasil hanya karena file ZIP/SQL terbentuk; periksa ukuran dan log perintah.

## 5. Pemulihan ke database uji (disarankan)

1. Buat database kosong terpisah, misalnya `eksidu_restore_test`.
2. Pulihkan dump ke database uji, bukan database aktif.
3. Ekstrak `storage-berkas.zip` ke direktori uji yang terpisah.
4. Salin `.env` khusus uji dan ubah `DB_DATABASE` serta disk storage agar menunjuk ke data uji. Jangan memakai storage produksi secara langsung.
5. Jalankan `php artisan config:clear`, `php artisan migrate:status`, lalu buka aplikasi uji.
6. Verifikasi beberapa arsip digital, file versi, histori perubahan, QR, dan pengajuan. Catat hasil pemulihan.

Contoh pemulihan SQL ke database uji:

```powershell
cmd /c "`"C:\xampp\mysql\bin\mysql.exe`" -u root -p eksidu_restore_test < `"D:\Backup-EKSiDU\20261113-120000\eksidu.sql`""
```

## 6. Pemulihan ke produksi

Pemulihan produksi hanya dilakukan oleh administrator yang berwenang setelah persetujuan dan backup kondisi saat ini. Jangan menimpa database aktif tanpa rencana rollback. Pastikan APP_KEY asli dipertahankan. Setelah pemulihan, verifikasi login, jumlah data, arsip, file privat, histori, serta alur pengajuan.

## 7. Batas implementasi

EKSiDU tidak mengonfigurasi backup otomatis atau penyimpanan off-site pada source ini. Penjadwalan, enkripsi media backup, retensi, dan uji restore berkala harus diatur oleh administrator sekolah sesuai kebijakan sekolah.
