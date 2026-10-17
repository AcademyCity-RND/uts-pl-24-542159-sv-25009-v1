# ⚙️ Backend API Service — Laravel 11 & FrankenPHP

Layanan RESTful API monorepo yang dibangun menggunakan framework **Laravel 11** dan dijalankan secara performan di atas **FrankenPHP Application Server**. Layanan ini mengelola autentikasi berbasis token personal serta operasi persistensi data CRUD ke basis data MySQL.

---

## 🛠️ Spesifikasi Tech Stack
- **Framework**: Laravel 11.x (API Mode)
- **Application Server**: FrankenPHP (PHP 8.3)
- **Authentication**: Laravel Sanctum (Bearer Token)
- **Database Driver**: MySQL 8.x / SQLite (In-memory Testing)
- **Testing Suite**: PHPUnit / Pest Framework

---

## 📌 Checklist Implementasi Backend (Terkait Soal UTS)

### ⏳ Task Terkait Soal 1: Backend RESTful API (15 Poin)
- [ ] Konfigurasi Laravel Sanctum untuk autentikasi API berbasis token.
- [ ] Endpoint `POST /api/login` (publik, memvalidasi kredensial dan menerbitkan bearer token).
- [ ] Endpoint `POST /api/logout` (terproteksi `auth:sanctum`, merevokasi token aktif).
- [ ] CRUD Resource lengkap (GET all, GET one, POST, PUT, DELETE) tersimpan di database.
- [ ] Standardisasi format request & response JSON dengan HTTP Status Code semantik (200, 201, 401, 422).
- [ ] Minimal 2 Feature Test otomatis (`tests/Feature/AuthTest.php` dan `tests/Feature/ResourceCrudTest.php`).
- [ ] Ekspor Postman Collection tersimpan di folder `/postman`.

### ⏳ Task Terkait Soal 5: Containerization Dockerfile (20 Poin)
- [ ] Pembuatan `backend/Dockerfile` menggunakan base image bertag versi jelas: `dunglas/frankenphp:1.4-php8.3-bookworm`.
- [ ] Penerapan *Docker Layer Caching*: menyalin `composer.json` & `composer.lock` sebelum kode aplikasi.
- [ ] Pembuatan `.dockerignore` untuk mengecualikan direktori `vendor/`, `.env`, dll.
- [ ] Memastikan tidak ada nilai sensitif (*secrets*) yang tertulis mentah di dalam Dockerfile.

### ⏳ Task Terkait Soal 7: Bonus Runtime FrankenPHP (10 Poin)
- [ ] Konfigurasi document root `/app/public` dan `SERVER_NAME=:80`.
- [ ] Verifikasi request API dilayani langsung oleh server aplikasi FrankenPHP melalui log container.

---

## 🔐 Spesifikasi Endpoint API

Semua endpoint (kecuali `/api/login`) diwajibkan menyertakan header:
```http
Authorization: Bearer <your_token_here>
Accept: application/json
```

| Method | Endpoint | Akses | Middleware | HTTP Status Success | Deskripsi |
| :---: | :--- | :---: | :---: | :--- : | :--- |
| `POST` | `/api/login` | Publik | `guest` | `200 OK` | Kredensial email/password $\rightarrow$ token |
| `POST` | `/api/logout` | Terproteksi | `auth:sanctum` | `200 OK` | Menghapus token aktif pengguna |
| `GET` | `/api/items` | Terproteksi | `auth:sanctum` | `200 OK` | Mengambil seluruh daftar entitas |
| `GET` | `/api/items/{id}` | Terproteksi | `auth:sanctum` | `200 OK` | Mengambil 1 rincian entitas |
| `POST` | `/api/items` | Terproteksi | `auth:sanctum` | `201 Created` | Membuat entitas baru |
| `PUT` | `/api/items/{id}` | Terproteksi | `auth:sanctum` | `200 OK` | Memperbarui entitas berdasarkan ID |
| `DELETE`| `/api/items/{id}` | Terproteksi | `auth:sanctum` | `200 OK` | Menghapus entitas berdasarkan ID |

---

## 📁 Struktur Direktori Layanan Backend

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/     # AuthController & ResourceController
│   │   └── Requests/            # FormRequest validasi data (422)
│   └── Models/                  # Model Eloquent (User, Resource)
├── bootstrap/                   # Inisialisasi framework
├── config/                      # Konfigurasi Sanctum, Database, dll
├── database/
│   ├── migrations/              # Skema tabel database
│   └── seeders/                 # Data seeder awal
├── routes/
│   └── api.php                  # Rute endpoint RESTful API
├── tests/
│   └── Feature/                 # Pengujian otomatis API
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
