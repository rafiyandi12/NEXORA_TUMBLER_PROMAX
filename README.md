# NEXORA TUMBLER — PRO V3

Versi ini adalah penyegaran UX/UI dari package NEXORA portable sebelumnya. Fokus utamanya: website lebih mudah dipakai pengguna awam dan alur belanja lebih familiar seperti marketplace modern, tanpa membuat tombol atau langkah terasa rumit.

## Yang diperbaiki

- Header sekarang memiliki pencarian yang jelas.
- Katalog memakai card produk yang lebih informatif dan CTA yang langsung terlihat.
- Detail produk memiliki quantity stepper, **Tambah ke keranjang**, dan **Beli sekarang**.
- Cart memakai ringkasan yang jelas dan tombol checkout yang menonjol.
- Checkout dibagi secara visual menjadi **Keranjang → Alamat → Pengiriman → Pembayaran**.
- Alamat tersimpan dapat dipilih dan otomatis mengisi form checkout.
- Pilihan kurir dan biaya dibuat dalam bentuk card yang mudah dibandingkan.
- Pilihan pembayaran dibuat seperti radio card marketplace.
- Halaman pembayaran memiliki kode, tombol salin, countdown, dan validasi kode sebelum status menjadi paid.
- Setelah pembayaran berhasil, pengguna langsung mendapat halaman konfirmasi yang jelas.
- Pesanan memiliki halaman detail, status/timeline, tracking, dan invoice.
- Profil sekarang memiliki data akun dan pengelolaan alamat.
- Mobile memakai bottom navigation sehingga pengguna tidak perlu mencari menu.
- Admin memiliki dashboard yang lebih mudah dipindai, kontrol status order, tracking number, QC, pembayaran, produk, laporan, dan invoice.
- Saat pembayaran kadaluarsa, stok barang dikembalikan agar tidak “terkunci” oleh order yang batal.

## Fitur utama

Customer: login/register, catalog, search/sort, product detail, add to cart, buy now, cart, saved addresses, checkout, shipping selection, payment method, payment code + countdown, payment success, notifications, order history, order detail, tracking, invoice PDF, profile.

Admin: dashboard, order status, tracking number, quality check, payment list, product/stock overview, sales report, order detail, invoice.

## Database

Package ini tetap portable dan terpisah dari project Laravel/MySQL Anda.

- Runtime database: `data/nexora.sqlite`
- Invoice PDF: `storage/invoices/`
- Tidak memakai database `nexora_tumbler` milik project Laravel.
- Database dibuat otomatis saat aplikasi pertama kali dijalankan.

## Produk

Tetap hanya menggunakan 7 listing CORKCICLE yang sudah ada pada project/source reference. Package ini sengaja tidak mengarang 30 produk atau detail produk yang tidak memiliki sumber.

## Cara menjalankan

### Opsi 1 — Windows

1. Extract ZIP.
2. Buka folder hasil extract di VS Code.
3. Pastikan PHP mempunyai SQLite:

```powershell
php -m | findstr /I "sqlite"
```

Yang dicari minimal `PDO`/`pdo_sqlite` dan `sqlite3`.

4. Jalankan `start.bat`.
5. Buka `http://127.0.0.1:8002`.

### Opsi 2 — Terminal

```powershell
cd C:\NEXORA_TUMBLER_PRO_V3
php -S 127.0.0.1:8002
```

Lalu buka `http://127.0.0.1:8002`.

Port `8002` dipilih supaya tidak bentrok dengan Laravel NEXORA yang sebelumnya memakai `8000` dan package portable lama yang memakai `8001`.

## Akun demo

Customer:
`customer@nexora.com`
`customer123`

Admin:
`admin@nexora.com`
`admin123`

## Alur yang disarankan untuk testing

Home → Produk → Detail → **Beli sekarang** → Checkout → pilih alamat → pilih kurir → pilih pembayaran → buat pesanan → kode pembayaran → masukkan kode yang benar → Pembayaran Berhasil → Detail Pesanan → Invoice PDF.

Untuk skenario cart:

Produk → Tambah ke keranjang → Keranjang → ubah jumlah → Checkout → lanjutkan seperti alur di atas.

## Catatan runtime

Di lingkungan pembuatan package ini, PHP CLI tersedia tetapi extension PDO SQLite tidak aktif, sehingga pengecekan yang dilakukan di sini adalah static/syntax check dan inspeksi struktur package. Runtime penuh harus diuji pada PHP Windows yang akan dipakai menjalankan website.


## V4 catalog revision

Versi ini menambahkan 20 listing produk baru CORKCICLE / kolaborasi CORKCICLE x Rifle Paper Co. dengan foto produk asli dari media/brand source, deskripsi performa yang bersumber dari materi CORKCICLE, serta seeding idempotent. Database lama tidak dihapus atau di-reset.

- Runtime: PHP built-in server di port 8002
- Database runtime: SQLite `data/nexora.sqlite`
- PDF invoice: `storage/invoices/`
- Foto katalog: URL gambar produk asli dari CORKCICLE / Rifle Paper Co.
- Stok tambahan 20 produk adalah stok demo untuk kebutuhan aplikasi lokal, bukan stok gudang real-time.
- Jalankan `php verify.php` setelah membuka project untuk memeriksa syntax, extension, jumlah katalog, dan referensi placeholder.
