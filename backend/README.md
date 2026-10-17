# ⚙️ Backend API Service — JejakBarang (Laravel 11 & FrankenPHP)

Layanan antarmuka pemrograman aplikasi (RESTful API) untuk sistem **JejakBarang** (Pusat Informasi *Lost & Found* Kampus) yang dibangun menggunakan **Laravel 11** dan dijalankan secara performan di atas **FrankenPHP Application Server**. Layanan ini mengelola autentikasi berbasis token Sanctum, otorisasi berbasis peran (Admin & User), unggahan berkas foto barang, logika pasif kedaluwarsa 3 hari (TTL), dan operasi CRUD pada basis data MySQL.

---

## 🛠️ Spesifikasi Tech Stack
- **Framework**: Laravel 11.x (API Mode)
- **Application Server**: FrankenPHP 1.4 (PHP 8.3)
- **Authentication**: Laravel Sanctum (Personal Access Token)
- **Authorization**: Laravel Policy & Gate (Respons `403 Forbidden`)
- **Database Driver**: MySQL 8.x (Testing: SQLite in-memory)
- **Testing Suite**: PHPUnit Framework

---

## 📌 Checklist Implementasi Backend (Terkait Soal UTS)

### ⏳ Task Terkait Soal 1: Backend RESTful API & Keamanan (15 Poin)
- [ ] Setup Laravel 11 dan instalasi paket `laravel/sanctum`.
- [ ] Endpoint `POST /api/login` (publik, memvalidasi kredensial dan menerbitkan bearer token).
- [ ] Endpoint `POST /api/logout` (terproteksi `auth:sanctum`, merevokasi token aktif).
- [ ] Skema database:
  - Migrasi `users` (penambahan kolom `role` enum: `admin`, `user` dan `phone_number`).
  - Migrasi `lost_items` (kategori hilang/ditemukan, lokasi, penyimpanan, kontak, status, approved_at, resolved_at, image_path).
- [ ] Proteksi *Mass Assignment*: Kolom `status`, `approved_at`, dan `resolved_at` dilindungi via `$guarded` pada Model `LostItem`.
- [ ] FormRequest Validasi (Respons 422 Unprocessable Entity):
  - `LoginRequest` (validasi email dan password).
  - `StoreLostItemRequest` (validasi field wajib & file gambar `mimes:jpg,jpeg,png|max:2048`).
  - `UpdateLostItemRequest` (validasi update teks dan file gambar baru).
- [ ] Penanganan HTTP Method Spoofing pada Update Gambar:
  - Dokumentasi dan dukungan request `POST` dengan `_method: 'PUT'` di FormRequest/Controller.
- [ ] Otorisasi Kebijakan (`LostItemPolicy`):
  - Memastikan hanya admin yang dapat mengubah status postingan ke `approved`/`rejected`.
  - Memastikan user hanya dapat menandai selesai (`resolved`) pada barang yang dilaporkan sendiri.
  - Mengembalikan status HTTP `403 Forbidden` jika otorisasi gagal.
- [ ] Standardisasi format request & response JSON dengan kode status HTTP semantik (200, 201, 401, 403, 422).
- [ ] Minimal 2 Feature Test otomatis terverifikasi lulus saat `php artisan test`:
  - `tests/Feature/AuthApiTest.php` (login gagal, login sukses, logout).
  - `tests/Feature/LostItemCrudTest.php` (tambah barang, validasi gagal, proteksi guest, otorisasi resolusi).
- [ ] Ekspor Postman Collection & Environment tersimpan di folder `/postman`.

### ⏳ Task Terkait Soal 5: Containerization Dockerfile (20 Poin)
- [ ] Pembuatan `backend/Dockerfile` menggunakan base image bertag versi jelas: `dunglas/frankenphp:1.4-php8.3-bookworm`.
- [ ] Penerapan *Docker Layer Caching*: menyalin `composer.json` & `composer.lock` terlebih dahulu sebelum kode aplikasi.
- [ ] Pembuatan `.dockerignore` untuk mengecualikan direktori `vendor/`, `.env`, dll.
- [ ] Bebas dari nilai kredensial/rahasia yang tertulis mentah (*hardcoded*).

### ⏳ Task Terkait Soal 7: Bonus Runtime FrankenPHP (10 Poin)
- [ ] Konfigurasi document root `/app/public` dan `SERVER_NAME=:80`.
- [ ] Dukungan eksekusi `php artisan storage:link` otomatis pada container runtime.
- [ ] Verifikasi request API dilayani langsung oleh server aplikasi FrankenPHP melalui log container.

---

## 🔐 Spesifikasi Endpoint API

Semua endpoint terproteksi diwajibkan menyertakan header:
```http
Authorization: Bearer <token_kamu>
Accept: application/json
```

| Method | Endpoint | Akses | Middleware | HTTP Status | Deskripsi |
| :---: | :--- | :---: | :---: | :---: | :--- |
| `POST` | `/api/login` | Publik | `guest` | `200 OK` | Kredensial email & password $\rightarrow$ Bearer Token |
| `POST` | `/api/logout` | Terproteksi | `auth:sanctum` | `200 OK` | Menghapus token aktif pengguna |
| `GET` | `/api/public/items` | Publik | - | `200 OK` | Browsing publik: hanya barang `approved` aktif (< 3 hari) |
| `GET` | `/api/items` | Terproteksi | `auth:sanctum` | `200 OK` | Mengambil data barang (Admin: semua inbox; User: miliknya) |
| `GET` | `/api/items/{id}` | Terproteksi | `auth:sanctum` | `200 OK` | Mengambil 1 rincian barang |
| `POST` | `/api/items` | Terproteksi | `auth:sanctum` | `201 Created` | Membuat laporan baru (Upload foto `multipart/form-data`) |
| `POST` | `/api/items/{id}` *(method: `PUT`)* | Terproteksi | `auth:sanctum` | `200 OK` | Memperbarui laporan (via spoofing `_method: PUT` untuk foto) |
| `DELETE`| `/api/items/{id}` | Terproteksi | `auth:sanctum` | `200 OK` | Menghapus laporan barang |
| `PATCH` | `/api/items/{id}/status` | Terproteksi | `auth:sanctum` | `200 OK` | Mengubah status postingan (Admin ACC/Reject, User Resolve) |
| `POST` | `/api/items/{id}/report` | Terproteksi | `auth:sanctum` | `200 OK` | Fitur lapor admin oleh pihak ketiga |
| `POST` | `/api/items/{id}/repost` | Terproteksi | `auth:sanctum` | `200 OK` | Memperpanjang masa aktif tayang (reset batas 3 hari) |

---

## 📁 Struktur Direktori Layanan Backend

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/     # AuthController.php & LostItemController.php
│   │   └── Requests/            # LoginRequest, StoreLostItemRequest, UpdateLostItemRequest
│   ├── Models/                  # User.php & LostItem.php
│   └── Policies/                # LostItemPolicy.php (Otorisasi 403 Forbidden)
├── bootstrap/                   # Inisialisasi framework
├── config/                      # Konfigurasi Sanctum, Database, Filesystem
├── database/
│   ├── migrations/              # Migrasi tabel users & lost_items
│   └── seeders/                 # DatabaseSeeder (Akun Admin & User demo)
├── routes/
│   └── api.php                  # Rute endpoint RESTful API
├── storage/app/public/lost_items# Direktori penyimpanan foto barang
├── tests/
│   └── Feature/                 # AuthApiTest.php & LostItemCrudTest.php
├── Dockerfile                   # Dockerfile berbasis FrankenPHP
├── .dockerignore                # Pengecualian build Docker
├── composer.json                # Dependensi PHP
└── README.md                    # Dokumentasi backend
```

---

## 🧪 Menjalankan Pengujian Otomatis (Feature Tests)
```bash
# Menjalankan di dalam container backend
docker compose exec backend php artisan test

# Atau jika di lingkungan lokal tanpa Docker
php artisan test
```
