# Arsip Sehat tes push dan pull hari senin

Aplikasi inventaris sederhana untuk puskesmas, dibangun dengan Laravel dan SQLite. Versi awal mencakup dashboard dan pengelolaan data barang tanpa halaman login.

## Fitur

- Mencatat barang habis pakai, seperti sabun dan kertas, dengan stok yang dapat diperbarui.
- Mencatat aset, seperti komputer dan printer, dengan nomor inventaris, jumlah, kondisi, tanggal perolehan, dan lokasi.
- Mengelola kategori barang dan kode kategori, lalu memilih kategori saat mengisi barang.
- Membentuk kode barang otomatis dari kode kategori dan nomor urut 4 digit, misalnya `ATK-0001`; nomor berikutnya melanjutkan nomor terbesar di kategori yang sama.
- Nomor kode barang tidak dapat diubah saat mengubah barang, kecuali kategorinya diganti; nomor baru diambil dari urutan berikutnya di kategori tujuan.
- Mengunggah foto barang dengan pratinjau sebelum disimpan.
- Menampilkan keypad angka 0–9 di dekat input jumlah dan harga; tombol di layar maupun keyboard sama-sama bisa dipakai.
- Menambah, melihat, mencari, mengubah, serta menghapus data barang.
- Menampilkan ringkasan jumlah data barang dan stok habis pakai.

## Menjalankan aplikasi

Pastikan PHP, Composer, Node.js, dan npm tersedia.

```powershell
composer install
npm install
php artisan migrate
php artisan storage:link
npm run build
php artisan serve
```

Buka alamat yang ditampilkan oleh `php artisan serve` di browser. Konfigurasi default menggunakan SQLite pada `database/database.sqlite`; pengaturan koneksi dapat diubah melalui `.env`.

Untuk pengembangan front-end, jalankan `npm run dev` pada terminal terpisah. Jalankan tes dengan:

```powershell
php artisan test
```
