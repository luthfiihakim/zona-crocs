# Zona Crocs: Inventory & Sales Management System

Aplikasi web manajemen **inventori dan penjualan** untuk toko sepatu Crocs UMKM (Zona Crocs, Medan), dibangun dengan **Laravel 12** dan **PostgreSQL**.

> **Tugas UTS** · Mata kuliah: `[isi mata kuliah]` · Nama: `[isi nama lengkap]` · NIM: `[isi NIM]` · Dosen: `[isi nama dosen]`

## Daftar isi

1. [Gambaran umum](#1-gambaran-umum)
2. [Fitur](#2-fitur)
3. [Aturan bisnis](#3-aturan-bisnis)
4. [Teknologi](#4-teknologi)
5. [Arsitektur](#5-arsitektur)
6. [Prasyarat](#6-prasyarat)
7. [Instalasi](#7-instalasi)
8. [Konfigurasi `.env`](#8-konfigurasi-env)
9. [Akun demo](#9-akun-demo)
10. [Pembayaran (Midtrans sandbox)](#10-pembayaran-midtrans-sandbox)
11. [Menjalankan tes](#11-menjalankan-tes)
12. [Keamanan](#12-keamanan)
13. [Struktur direktori](#13-struktur-direktori)
14. [Dokumentasi tambahan](#14-dokumentasi-tambahan)
15. [Keterbatasan dan rencana pengembangan](#15-keterbatasan-dan-rencana-pengembangan)
16. [Catatan penggunaan data dan foto](#16-catatan-penggunaan-data-dan-foto)

---

## 1. Gambaran umum

**Latar belakang.** Zona Crocs adalah usaha kecil di Medan yang menjual Crocs seri Classic, Collab, Platform, dan Geo. Pesanan masuk lewat DM Instagram dan stok dicatat manual. Cara itu rawan stok selisih, ukuran yang sudah habis tidak terpantau, dan dua pembeli bisa memesan ukuran terakhir yang sama.

**Solusi.** Aplikasi ini mencatat stok per **varian (produk × warna × ukuran)**, mengubah stok otomatis saat barang masuk dan keluar, menolak penjualan jika stok tidak cukup, dan menyediakan toko online dengan pembayaran lewat payment gateway.

**Tiga peran pengguna:** Owner/Admin, Staff (kasir/admin toko), dan Customer (pembeli online).

## 2. Fitur

| Area | Admin | Staff | Customer |
|---|:-:|:-:|:-:|
| Login, logout, register (customer) | ✔ | ✔ | ✔ |
| Dashboard (omzet, transaksi, produk terlaris, stok rendah) | ✔ | ✔ | |
| CRUD kategori, supplier, produk, varian | ✔ | ✔ (tanpa hapus) | |
| Generator varian (warna × ukuran) dan SKU unik | ✔ | ✔ | |
| Foto produk: unggah banyak, ganti, hapus, atur urutan per warna | ✔ | ✔ | |
| Pembelian / barang masuk (banyak item, stok bertambah otomatis) | ✔ | ✔ | |
| Penjualan kasir / barang keluar (banyak item, stok berkurang otomatis) | ✔ | ✔ | |
| Batalkan pembelian atau penjualan (stok dikembalikan) | ✔ | | |
| Halaman stok rendah | ✔ | ✔ | |
| Manajemen pengguna: tambah, ubah role, aktif/nonaktif akun | ✔ | | |
| Katalog: cari, filter kategori dan ukuran, urutkan harga | ✔ (pratinjau) | ✔ (pratinjau) | ✔ |
| Halaman produk: pilih warna, ukuran, galeri foto yang bisa digeser, panduan ukuran | ✔ (pratinjau) | ✔ (pratinjau) | ✔ |
| Keranjang, checkout, bayar online (Midtrans), riwayat pesanan | | | ✔ |
| Batalkan pesanan sendiri yang belum dibayar | | | ✔ |

Fitur umum: pencarian, filter, dan paginasi di daftar utama; pesan validasi berbahasa Indonesia; tampilan responsif untuk HP.

## 3. Aturan bisnis

- **Stok per varian**, dengan SKU unik. Ukuran **M11 dan M12 adalah PO** dan tidak dijual dari stok.
- **Stok tidak boleh negatif.** Dijaga tiga lapis: validasi di service, kunci baris `lockForUpdate()`, dan `CHECK (stock >= 0)` di database.
- **Harga dan total dihitung di server** dari database. Harga saat transaksi disalin ke `unit_price` di item, sehingga riwayat tidak berubah jika harga produk berubah.
- **Semua perubahan stok memakai `DB::transaction`.** Jika satu item gagal, seluruh transaksi dibatalkan (rollback).
- **Pembelian** menambah stok. Pembatalan hanya oleh admin dan hanya jika stok hasil pembelian itu belum terpakai.
- **Penjualan kasir** langsung berstatus lunas. **Pesanan online** menahan stok saat checkout dan menunggu pembayaran selama **60 menit**. Lewat batas itu pesanan otomatis kedaluwarsa dan stok kembali. Satu customer maksimal punya **3 pesanan belum dibayar**.
- **Produk yang masih punya stok tidak bisa dihapus.** Produk dan supplier memakai soft delete agar riwayat transaksi tetap utuh.
- **Akun nonaktif tidak bisa login** dan sesinya berakhir pada request berikutnya. Admin tidak bisa mengubah role atau menonaktifkan akunnya sendiri.
- **Pembayaran:** notifikasi diverifikasi dengan signature SHA-512, nominal dicocokkan, dan penanganannya idempotent. Pembayaran yang terlambat menghidupkan kembali pesanan jika stok masih ada, atau menandainya **PERLU REFUND**.

## 4. Teknologi

| Komponen | Pilihan |
|---|---|
| Framework | Laravel 12 (PHP 8.2) |
| Database | PostgreSQL (contoh: Neon, koneksi TLS) |
| Tampilan | Blade dan CSS sendiri (`public/css/app.css`), tanpa proses build front-end |
| Payment gateway | Midtrans Snap (**sandbox**) |
| Pengujian | PHPUnit (feature test) |

## 5. Arsitektur

Alur request mengikuti pola yang diminta:

```
Route → Middleware (auth, active, role) → Controller → Form Request → Service → Model → Database
```

| Lapisan | Isi |
|---|---|
| Middleware | `EnsureRole` (admin/staff/customer), `EnsureActive` (akun aktif) |
| Form Request | validasi untuk kategori, supplier, produk, varian, pembelian, penjualan, checkout, foto, user |
| Policy | `SalePolicy` (customer hanya boleh melihat, membayar, dan membatalkan pesanannya sendiri) |
| Service | `SaleService`, `PurchaseService`, `PaymentService`, `MidtransService`, `CartService`, `ProductImageService` |
| Command | `sales:expire` (kedaluwarsakan pesanan), `images:import` (daftarkan foto), `images:check` |

Aturan bisnis ditulis di **service**, bukan di controller, supaya satu aturan dipakai ulang oleh kasir dan checkout online.

## 6. Prasyarat

- **PHP ≥ 8.2** dengan ekstensi: `pdo_pgsql`, `pgsql`, `intl`, `mbstring`, `openssl`, `curl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, dan `zip` (disarankan)
- **Composer 2**
- **PostgreSQL** (cloud atau lokal) dan Git
- Node.js **tidak diperlukan**

Cek ekstensi PostgreSQL dan intl: `php -m | Select-String "pgsql|intl"` (PowerShell) atau `php -m | grep -E "pgsql|intl"`.

## 7. Instalasi

```bash
# 1. Ambil kode
git clone https://github.com/luthfiihakim/zona-crocs.git
cd zona-crocs

# 2. Pasang dependensi
composer install

# 3. Salin file konfigurasi
cp .env.example .env            # PowerShell: Copy-Item .env.example .env

# 4. Buat kunci aplikasi
php artisan key:generate

# 5. Isi kredensial database di .env (lihat bagian 8)

# 6. Buat tabel dan isi data awal
php artisan migrate --seed

# 7. Jalankan
php artisan serve
```

Buka `http://127.0.0.1:8000`.

- `php artisan migrate --seed` membuat 12 tabel aplikasi, lalu mengisi 4 kategori, 9 ukuran, 24 produk, 423 varian, 2 supplier contoh, 3 akun demo, dan mendaftarkan foto produk dari `public/images/products`. Untuk mengulang dari nol gunakan `php artisan migrate:fresh --seed` (**menghapus semua data** di database itu).
- Opsional, agar pesanan yang tidak dibayar otomatis kedaluwarsa di latar belakang: jalankan `php artisan schedule:work` di terminal lain. Tanpa itu, pesanan tetap dikedaluwarsakan saat halamannya dibuka.

## 8. Konfigurasi `.env`

| Variabel | Keterangan |
|---|---|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Kredensial PostgreSQL |
| `DB_SSLMODE` | `require` (wajib untuk database cloud) |
| `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY` | Kunci **sandbox** dari dashboard Midtrans |
| `MIDTRANS_IS_PRODUCTION` | `false` |
| `APP_DEBUG` | `false` untuk penilaian dan demo (tidak membocorkan detail error) |

**Kredensial database untuk penilai dikirim terpisah** (tidak disimpan di repositori). File `.env` tidak ikut Git, hanya `.env.example`.

**Catatan koneksi (PHP/libpq lama).** Jika muncul error `Endpoint ID is not specified` saat memakai Neon, isi password dengan format berikut (gunakan tanda kutip tunggal):

```
DB_PASSWORD='endpoint=ENDPOINT_ID$PASSWORD_ASLI'
```

`ENDPOINT_ID` adalah bagian pertama dari host database, misalnya `ep-nama-acak-12345`.

Tanpa kunci Midtrans, semua fitur tetap berjalan kecuali tombol **Bayar Sekarang** di pesanan online.

## 9. Akun demo

| Role | Email | Password |
|---|---|---|
| Admin / Owner | `admin@zonacrocs.test` | `password123` |
| Staff | `staff@zonacrocs.test` | `password123` |
| Customer | `customer@zonacrocs.test` | `password123` |

Akun ini hanya untuk demo. Ganti atau hapus sebelum dipakai nyata.

## 10. Pembayaran (Midtrans sandbox)

1. Daftar akun **sandbox** di Midtrans, lalu salin Server Key dan Client Key dari **Settings → Access Keys** ke `.env`. Jalankan `php artisan config:clear`.
2. Login sebagai customer, masukkan produk ke keranjang, lalu **checkout**. Pesanan berstatus *Menunggu bayar* dan stok ditahan.
3. Buka pesanan, klik **Bayar Sekarang**. Popup Snap muncul. Untuk kartu uji, gunakan data dari halaman *Sandbox Test Payment* di dokumentasi Midtrans.
4. Setelah bayar, status pesanan menjadi **Lunas**. Server mengecek status ke Midtrans, bukan mengandalkan callback browser.

**Webhook (opsional).** Endpoint notifikasi: `POST /midtrans/notification`. Karena Midtrans tidak bisa memanggil `localhost`, uji webhook dengan tunnel publik (misalnya Cloudflare Tunnel atau ngrok) dan isi *Payment Notification URL* di dashboard sandbox. Tanpa tunnel, status tetap tersinkron setiap kali halaman pesanan dibuka.

## 11. Menjalankan tes

Tes memakai database **terpisah** karena `RefreshDatabase` menjalankan `migrate:fresh`.

1. Buat database kosong bernama `zonacrocs_test` di server PostgreSQL yang sama.
2. Nama database sudah dipaksa di `phpunit.xml`. Host, user, dan password dibaca dari `.env`.
3. Jalankan:

```bash
php artisan test
```

Terdapat **75 tes otomatis**. Pengaman di `tests/TestCase.php` menghentikan tes jika nama database tidak mengandung kata `test`, sehingga data asli tidak tersentuh. Daftar lengkap skenario ada di [docs/TEST_CASES.md](docs/TEST_CASES.md).

Catatan: database cloud gratis bisa lambat pada koneksi pertama. Jalankan `php artisan db:show` sekali sebelum menjalankan tes.

## 12. Keamanan

- **Autentikasi:** password di-hash (bcrypt), regenerasi sesi saat login, invalidasi sesi saat logout, akun bisa dinonaktifkan.
- **Otorisasi di server:** middleware `role` untuk area admin/staff/customer dan `SalePolicy` untuk data milik customer. Tidak hanya menyembunyikan menu.
- **CSRF** aktif di semua form. Satu-satunya pengecualian adalah webhook pembayaran, yang dijaga verifikasi signature.
- **Pembatasan percobaan:** login dibatasi 6 kali per menit dan registrasi 10 kali per menit per alamat IP.
- **Validasi** lewat Form Request. Field sensitif (`role`, `is_active`) tidak masuk `$fillable` sehingga tidak bisa diselundupkan lewat form (mass assignment).
- **Output di-escape** oleh Blade (XSS). Teks dari database dimasukkan ke JavaScript lewat `textContent` atau `@json`.
- **Integritas data di database:** foreign key, unique, dan `CHECK` constraint (stok tidak negatif, kuantitas lebih dari 0, harga tidak negatif).
- **Unggah foto:** hanya JPG, PNG, WEBP; maksimal 3 MB; minimal 300×300 px; nama berkas dibuat server dan ekstensi diambil dari isi berkas.
- **Rahasia:** kunci dan kredensial hanya di `.env` (tidak masuk Git). `.env.example` tidak berisi nilai rahasia dan memakai `APP_DEBUG=false`.
- **Pembayaran:** signature diverifikasi dengan `hash_equals`, nominal dicocokkan, notifikasi berulang tidak mengubah stok dua kali.

## 13. Struktur direktori

```
app/
  Console/Commands/      sales:expire, images:import, images:check
  Http/
    Controllers/         Auth, Category, Supplier, Product, ProductVariant, ProductImage,
                         Purchase, Sale, Catalog, Cart, Checkout, Order, Payment,
                         MidtransNotification, Dashboard, User
    Middleware/          EnsureRole, EnsureActive
    Requests/            Form Request per fitur
  Models/                User, Category, Supplier, Size, Product, ProductVariant,
                         ProductImage, Purchase, PurchaseItem, Sale, SaleItem, Payment
  Policies/              SalePolicy
  Services/              SaleService, PurchaseService, PaymentService, MidtransService,
                         CartService, ProductImageService
database/
  migrations/            12 tabel aplikasi
  seeders/               pengguna, ukuran, katalog, supplier
public/images/products/  foto produk (satu folder per produk)
resources/views/         layouts, auth, katalog, keranjang, checkout, pesanan, admin
routes/web.php
tests/Feature/           75 tes otomatis
docs/                    ERD, test case, screenshot, bukti
```

## 14. Dokumentasi tambahan

- **[docs/ERD.md](docs/ERD.md)**: ERD final (12 tabel) dan penjelasan relasi.
- **[docs/TEST_CASES.md](docs/TEST_CASES.md)**: test case otomatis dan manual beserta hasilnya.

## 15. Keterbatasan dan rencana pengembangan

**Keterbatasan saat ini**

1. **Pembayaran hanya mode sandbox** (tanpa uang asli). Mode produksi Midtrans memerlukan verifikasi bisnis.
2. **Refund tidak otomatis.** Pembatalan pesanan hanya mengembalikan stok. Pengembalian dana dilakukan manual di dashboard payment gateway. Pesanan yang dibayar terlambat saat stok sudah habis ditandai **PERLU REFUND** di catatan pesanan.
3. **Pre-order (ukuran M11 dan M12) belum punya sistem pemesanan.** Ukuran tersebut ditampilkan sebagai PO dan tidak bisa dibeli online. Pemesanannya diproses manual lewat chat toko.
4. **Keranjang disimpan di sesi**, sehingga kosong setelah logout atau sesi berakhir.
5. **Webhook membutuhkan alamat publik.** Di lingkungan lokal, status pembayaran disinkronkan saat halaman pesanan dibuka dan oleh jadwal `sales:expire`.
6. **Database cloud paket gratis** bisa lambat atau memutus koneksi pada request pertama setelah lama tidak dipakai. Transaksi yang terputus dibatalkan otomatis sehingga data tetap konsisten.
7. **Belum ada ongkos kirim, ekspedisi, dan nomor resi.** Alamat pengiriman hanya dicatat.
8. **Belum ada email notifikasi** dan hanya satu bahasa (Indonesia).
9. Berjalan lewat HTTP di lingkungan lokal. Untuk produksi perlu HTTPS dengan `SESSION_SECURE_COOKIE=true`, backup database terjadwal, dan monitoring.
10. **Foto produk** berasal dari materi promosi toko dengan kualitas terbatas. Foto asli bisa diunggah lewat halaman admin tanpa mengubah kode.

**Rencana pengembangan:** sistem PO dengan bayar penuh di muka, halaman terpusat untuk melengkapi foto produk, ongkos kirim, stok opname (penyesuaian stok dengan alasan), laporan penjualan per periode, dan pengubahan ukuran foto otomatis.

## 16. Catatan penggunaan data dan foto

Nama toko, daftar produk, harga, dan ukuran digunakan **dengan izin pemilik usaha**. Tidak ada data pelanggan asli di repositori ini; akun dan stok awal adalah data demo. Foto produk digunakan untuk keperluan tugas kuliah dengan izin pemilik toko. Merek dan karakter pada produk adalah milik pemegang haknya masing-masing.