# Panduan Pemetaan Arsip Dokumen EKSiDU

**Snapshot:** 13 November 2026

## Model baru

Pilihan layanan Siswa tidak lagi mengambil daftar dari pemetaan layanan lama `DocumentType`. Satu sumber ketersediaan untuk alur baru adalah `student_document_archives`, yang terikat ke `user_id` siswa. Kategori yang tersedia:

- `rapor`: tahun ajaran, semester 1/2, periode tengah/akhir.
- `tka`: tahun terbit (jika diketahui).
- `skls`: Surat Keterangan Lulus.
- `ijazah`: tahun terbit (jika diketahui).
- Legalisir memilih salah satu kategori dokumen tersebut sebagai sumber.

Status Fisik dan Digital dipisahkan. Digital hanya tersedia jika status `tersedia`, ada relasi dokumen, dan file benar-benar ditemukan pada disk privat. Fisik hanya tersedia jika status fisik `tersedia`. Status Hilang/Rusak tidak dapat dipilih untuk layanan fisik.

## Mengelola arsip

1. Login Admin/TU.
2. Buka **Arsip Dokumen Siswa**.
3. Cari siswa menggunakan nama/NIS/kelas.
4. Tambah arsip sesuai kategori. Untuk Rapor wajib isi tahun ajaran, semester, dan periode.
5. Upload PDF/JPG/PNG untuk ketersediaan digital. File disimpan di disk privat.
6. Atur status fisik secara terpisah, termasuk lokasi.
7. Gunakan Edit Metadata untuk koreksi. Gunakan histori untuk melihat perubahan.
8. Ganti file dengan upload versi baru; file versi sebelumnya tidak ditimpa dan pengajuan yang sudah dibuat tetap menunjuk snapshot versinya.

## Standar kelengkapan dan Tidak Berlaku

Admin dapat menambahkan standar berdasarkan kelas, kategori, tahun ajaran, semester, dan periode. Aturan yang tidak memiliki kelas berlaku untuk semua kelas. Pada detail siswa, Admin dapat mengecualikan aturan sebagai **Tidak Berlaku** dengan alasan; pengecualian disimpan sebagai histori dan tidak dihitung sebagai kekurangan.

## Pemetaan dokumen lama

Migration arsip 13 November hanya memetakan kode `IJZ` yang jelas ke kategori Ijazah, dengan status digital bergantung pada keberadaan file. Dokumen `SKL` dan `SKHUN` tidak dipetakan otomatis ke `SKLS`. Rapor lama yang tidak memiliki periode tengah/akhir juga tidak dipetakan otomatis. Admin harus memeriksa data lama dan membuat arsip baru secara manual bila informasi lengkap telah dikonfirmasi.

Tabel `documents`, `document_loans`, `physical_inventory_items`, dan `physical_loans` tidak dihapus. Histori lama dipertahankan; jangan menjalankan penghapusan massal atau `migrate:fresh`.

## Pemeriksaan

- [ ] Siswa hanya melihat arsip miliknya.
- [ ] Rapor memerlukan semester dan periode yang cocok.
- [ ] TKA tidak meminta semester/periode rapor.
- [ ] Legalisir menampilkan kategori dokumen.
- [ ] File digital hilang/terhapus tidak ditampilkan sebagai tersedia.
- [ ] Status fisik dan digital tidak saling memengaruhi.
- [ ] Dokumen Tidak Berlaku tidak dihitung sebagai kekurangan.
- [ ] Histori dan versi lama tetap tersedia.
- [ ] SKL/SKHUN tidak otomatis disamakan dengan SKLS.
