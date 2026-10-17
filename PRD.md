# 📋 Product Requirements Document (PRD) & Technical Architecture
# JejakBarang — Pusat Lost & Found Anti-Tenggelam

> **Repositori Pegangan Teknis:** UTS Konstruksi dan Evolusi Perangkat Lunak (KEPL)  
> **Arsitektur:** Decoupled Monorepo (Laravel 11 FrankenPHP REST API & Vue.js 3 SPA)  
> **Infrastruktur:** Docker Compose Orchestration & CI/CD GitHub Actions ke GHCR

---

## 1. Deskripsi Singkat & Latar Belakang

**JejakBarang** adalah aplikasi web *client-server* modern yang berfungsi sebagai papan pengumuman interaktif terpusat untuk barang hilang dan ditemukan (*Lost & Found*) di lingkungan kampus. Masalah utama yang diselesaikan adalah fenomena pengumuman barang hilang di grup obrolan (WhatsApp/Telegram) dan media sosial yang sangat cepat **tenggelam** tertimbun pesan lain, tidak terverifikasi, dan sulit dilacak status akhirnya (apakah sudah kembali ke pemiliknya atau belum).

Sistem ini menerapkan alur kendali **"User Propose $\rightarrow$ Admin Approve"** untuk menjamin keabsahan informasi sebelum tayang di ranah publik. Aplikasi dibangun dengan pemisahan peran yang tegas (*Decoupled Architecture*): Backend Laravel 11 berperforma tinggi di atas server aplikasi **FrankenPHP**, Frontend SPA interaktif berbasis **Vue.js 3**, penyimpanan relasional **MySQL** yang persisten, serta kontainerisasi penuh menggunakan **Docker Compose**.

---

## 2. Tech Stack

| Komponen | Teknologi | Keterangan & Peran |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 11.x | Framework PHP inti penyedia RESTful API murni (JSON) |
| **Application Server** | FrankenPHP (PHP 8.3) | Pengganti modern untuk PHP-FPM / Nginx / `artisan serve` |
| **Authentication** | Laravel Sanctum | Autentikasi berbasis *Personal Access Token* (Bearer Token) |
| **Frontend Framework**| Vue.js 3 | Single Page Application (SPA) berbasis Composition API (`<script setup>`) |
| **HTTP Client & Router**| Axios & Vue Router 4 | Penanganan request asinkron dengan interceptor & manajemen rute SPA |
| **Database** | MySQL 8.x | RDBMS dengan persistensi data via Docker Named Volume |
| **Infrastructure** | Docker & Docker Compose | Kontainerisasi multi-layanan (DB, BE, FE) dalam satu jaringan virtual |
| **CI/CD Pipeline** | GitHub Actions & GHCR | Pengujian otomatis, kompilasi aset, build Docker, & push ke `ghcr.io` |
| **Testing Suite** | PHPUnit & Postman | Automated Feature Testing dan verifikasi koleksi endpoint API |

---

## 3. Ketentuan & Arsitektur Fungsional

### A. Backend API (Laravel 11 & FrankenPHP)

#### 1. Endpoint Autentikasi:
* `POST /api/login`: Memvalidasi kredensial (email & password) dan menerbitkan Bearer Token Sanctum.
* `POST /api/logout`: Dilindungi `auth:sanctum`, merevokasi dan menghapus token aktif pengguna.

#### 2. Rute Akses Publik vs Terproteksi:
* **Rute Publik (Guest Browsing):**
  * `GET /api/public/items`: Menampilkan daftar barang hilang/ditemukan yang berstatus `approved` dan masa aktifnya belum kedaluwarsa (< 3 hari). Endpoint ini dapat diakses bebas tanpa token oleh peramban tamu.
* **Rute Terproteksi (Wajib Middleware `auth:sanctum`):**
  * Seluruh endpoint CRUD dan pengelolaan wajib dilindungi Sanctum:
    * `GET /api/items`: Mengambil daftar barang untuk kebutuhan dashboard authenticated (Admin melihat seluruh status/tab inbox; User melihat daftar barang yang ia laporkan sendiri).
    * `GET /api/items/{id}`: Menampilkan detail 1 barang secara lengkap.
    * `POST /api/items`: Membuat laporan barang baru (otomatis berstatus `pending`, menyertakan file gambar via `multipart/form-data`).
    * `POST /api/items/{id}` *(dengan `_method: PUT`)*: Memperbarui data laporan barang (mendukung update file gambar).
    * `DELETE /api/items/{id}`: Menghapus data laporan barang (hanya pemilik atau admin).
    * `PATCH /api/items/{id}/status`: Memperbarui status laporan (`approved`, `rejected`, `resolve_requested`, `resolved`).
    * `POST /api/items/{id}/report`: Fitur lapor admin bagi pengguna lain (non-owner).
    * `POST /api/items/{id}/repost`: Memperpanjang masa tayang barang aktif (reset batas waktu 3 hari).

#### 3. Penanganan Unggahan Gambar & Method Spoofing (`PUT`):
* **Limitasi Native PHP Stream:** PHP secara bawaan tidak dapat membaca payload `$_FILES` pada HTTP method `PUT` murni dengan `multipart/form-data`.
* **Solusi Arsitektural:** Pada proses pembaruan data (Update) yang melibatkan foto baru, Frontend mengirimkan request via HTTP Method **`POST`** dengan menyisipkan field spoofing **`_method: 'PUT'`** ke dalam objek `FormData`. Laravel akan secara otomatis membaca dan mengeksekusi request tersebut melalui rute `Route::put('/api/items/{id}', ...)`.
* Berkas gambar divalidasi (`mimes:jpg,jpeg,png|max:2048`) dan disimpan di direktori `storage/app/public/lost_items/`.

#### 4. Otorisasi & Keamanan:
* **Perlindungan Mass Assignment:** Kolom sensitif seperti `status`, `approved_at`, dan `resolved_at` wajib dilindungi dari Mass Assignment (dikeluarkan dari array `$fillable` atau didefinisikan dalam `$guarded`).
* **Laravel Policy & Gate:** Memastikan bahwa aksi modifikasi atau resolusi barang oleh pihak yang tidak berhak akan ditolak dengan mengembalikan respons HTTP **`403 Forbidden`**.

#### 5. Format Standar & HTTP Status Code:
* Seluruh respons berformat standar JSON:
  * `200 OK`: Permintaan berhasil diselesaikan.
  * `201 Created`: Entitas laporan barang baru berhasil dibuat.
  * `401 Unauthorized`: Token tidak valid, kedaluwarsa, atau kredensial salah.
  * `403 Forbidden`: Pengguna tidak memiliki hak otorisasi untuk resource target.
  * `422 Unprocessable Entity`: Validasi input data pada `FormRequest` gagal.

---

### B. Database Schema

#### Tabel `users`:
* `id` (BigIncrements, Primary Key)
* `name` (String)
* `email` (String, Unique)
* `password` (String, Hashed)
* `role` (Enum: `admin`, `user` — default: `user`)
* `phone_number` (String, Nullable)
* `created_at` & `updated_at` (Timestamp)

#### Tabel `lost_items`:
* `id` (BigIncrements, Primary Key)
* `user_id` (ForeignKey $\rightarrow$ `users.id`, Cascade on Delete)
* `item_name` (String: nama barang hilang/ditemukan)
* `category` (Enum: `lost`, `found`)
* `location` (String: tempat terakhir terlihat atau ditemukan)
* `current_storage` (String: posisi barang saat ini, misal: "Dititipkan di Pos Satpam Utama", "Dibawa Penemu")
* `contact_info` (String: kontak penanggung jawab barang saat ini)
* `reporter_contact_info` (String: nomor kontak pelapor/pembuat postingan)
* `chronology` (Text: deskripsi kronologi kejadian)
* `status` (Enum: `pending`, `approved`, `rejected`, `resolve_requested`, `resolved` — default: `pending`)
* `image_path` (String, Nullable: path relatif file gambar yang diunggah)
* `approved_at` (Timestamp, Nullable: waktu disetujui oleh admin)
* `resolved_at` (Timestamp, Nullable: waktu barang berhasil diselesaikan/dikembalikan)
* `created_at` & `updated_at` (Timestamp)

---

### C. Alur Kerja Resolusi Barang (Resolution Flow)

```text
Kasus A: Diselesaikan oleh Pemilik Sendiri
[Pemilik Barang] ──(Klik Tombol "Selesaikan")──> [Status: 'resolved'] (resolved_at = now())

Kasus B: Ditemukan oleh Pihak Lain (Fitur Lapor Admin)
[Pihak Ketiga] ──(Lapor Admin: Barang Sudah Ditemukan)──> [Admin Inbox]
                                                                │
[Admin] ──(Kirim Permintaan Resolusi)───────────────────────────┘
   │
   ▼
[Status: 'resolve_requested']
   │
   ▼ (Notifikasi Muncul di Dashboard Pelapor)
[Pelapor Memilih]:
   ├── "Terima" ──> [Status: 'resolved'] (resolved_at = now())
   └── "Tolak"  ──> [Status: 'approved'] (Kembali tayang aktif di publik)
```

---

### D. Mekanisme Masa Aktif & Passive TTL 3 Hari

1. **Logika Bisnis Pasif (Tanpa Daemon Cronjob):**
   * Setiap postingan yang di-*approve* admin mencatat waktu persetujuan pada kolom `approved_at`.
   * Pada pemanggilan rute publik `GET /api/public/items`, database menyaring barang secara pasif:
     ```php
     LostItem::where('status', 'approved')
         ->where('approved_at', '>=', now()->subDays(3))
         ->latest('approved_at')
         ->get();
     ```
   * Postingan yang telah melewati 3 hari secara otomatis tidak dimunculkan di publik tanpa membebani sistem dengan penjadwal background (*cronless approach*).
2. **Countdown Timer UI:**
   * Frontend menghitung sisa waktu tayang aktif:
     $$\text{Sisa Waktu} = (\text{approved\_at} + 3\text{ hari}) - \text{Waktu Sekarang}$$
   * Ditampilkan pada komponen `CountdownTimer.vue` di setiap kartu barang.
3. **Fitur Repost:**
   * Pelapor dapat memperpanjang masa tayang barang yang telah kedaluwarsa dengan menekan tombol **Repost** yang memperbarui nilai `approved_at = now()`.

---

### E. Frontend SPA (Vue.js 3 & Vite)

#### 1. Arsitektur Komponen Modular (Reusable Components):
* `CardItem.vue`: Kartu representasi barang lengkap dengan foto, badge kategori, lokasi, dan countdown timer.
* `StatusBadge.vue`: Komponen penanda status visual (Pending: Kuning, Approved: Hijau, Resolve Requested: Biru, Resolved: Abu-abu, Rejected: Merah).
* `CountdownTimer.vue`: Indikator waktu sisa pasif 3 hari secara *real-time*.
* `BaseModal.vue`: Modal dialog reusable untuk form konfirmasi hapus, preview gambar, dan form pelaporan.
* `Navbar.vue`: Navigasi responsif yang mendeteksi state login user/admin.

#### 2. Struktur Antarmuka & Halaman:
* **Dashboard Publik (`/`):** Tampilan grid kartu barang yang berstatus `approved` tanpa memerlukan login.
* **Halaman Login & Register (`/login` & `/register`):** Formulir autentikasi pengguna. Token disimpan di `localStorage` dan status peran disimpan di store state.
* **Halaman Lapor Barang (`/report`):** Formulir submit barang hilang/ditemukan dengan upload gambar `FormData`.
* **Dashboard Admin (`/admin`):**
  * **Tab 1 — Pengajuan Pending:** Meninjau laporan masuk (tombol ACC / Tolak).
  * **Tab 2 — Laporan Aktif Publik:** Memantau barang yang sedang tayang, dengan opsi hapus paksa jika melanggar.
  * **Tab 3 — Pesan Resolusi:** Memproses pengajuan resolusi dari penemu/pelapor.

#### 3. Penanganan Error Global (Error Handling UI/UX):
* Axios Interceptor mendeteksi kegagalan koneksi (*Network Error*, *503 Service Unavailable*, atau *Connection Refused*).
* Sistem menampilkan antarmuka ramah pengguna (*"Server Sedang Dalam Pemeliharaan / Layanan Belum Tersedia"*) alih-alih layar putih kosong (*blank screen*).

---

## 4. Infrastruktur & Orkestrasi Kontainer

### A. Jaringan Terpadu Docker Compose
* **Frontend Service (`frontend`):** Port host `80:80`. Berbasis Nginx 1.27 Alpine.
* **Backend Service (`backend`):** Port host `8000:80`. Berbasis FrankenPHP 1.4 PHP 8.3.
* **Database Service (`database`):** Port host `3306:3306`. Berbasis MySQL 8.0.

### B. Konfigurasi Nginx Reverse Proxy (`frontend/nginx.conf`)
Menghubungkan frontend SPA ke service backend melalui nama jaringan Docker internal:
```nginx
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;

    # SPA Routing Fallback
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Reverse Proxy API ke Backend FrankenPHP
    location /api/ {
        proxy_pass http://backend:80/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Reverse Proxy Storage Gambar ke Backend
    location /storage/ {
        proxy_pass http://backend:80/storage/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### C. Persistensi Data (Named Volumes)
Berkas `docker-compose.yml` mengelola 2 Named Volume penting:
1. `db_data`: Menjamin data tabel MySQL tidak terhapus saat container di-*down* atau di-*rebuild*.
2. `backend_storage`: Terpasang pada direktori `/app/storage/app/public` di container backend, menjamin seluruh file gambar yang diunggah pengguna tetap aman tersimpan.

---

## 5. Git Workflow & Otomatisasi CI/CD

### A. Strategi Branching & Aturan Kontribusi
* **`main`**: Branch stabil siap produksi (*Production-ready*).
* **`dev`**: Branch integrasi dan pengujian fitur (*Staging*).
* **`feature/*`**: Branch isolasi untuk pengembangan fitur spesifik (misal: `feature/backend-api`, `feature/frontend-spa`).
* **Pull Request Rule:** Dilarang melakukan *direct push* ke `main` dan `dev`. Seluruh penggabungan kode wajib melalui Pull Request (PR) dan lolos status checks CI.

### B. Pipeline CI/CD (`.github/workflows/ci.yml`)
1. **Continuous Integration (CI):**
   * Dipicu pada setiap PR ke `main`/`dev` atau push ke `dev`/`feature/*`.
   * **Job 1 (Backend Test):** Menjalankan environment PHP 8.3, instalasi dependensi Composer, dan eksekusi otomatis `php artisan test`.
   * **Job 2 (Frontend Build):** Menjalankan environment Node.js 20, instalasi `npm ci`, dan kompilasi `npm run build`.
2. **Continuous Deployment (CD):**
   * Dipicu **HANYA** saat terjadi push/merge ke branch `main`.
   * Membaca rahasia dari GitHub Secrets (`APP_KEY`, kredensial DB).
   * Membangun Docker Image Backend & Frontend.
   * Mengunggah (*push*) image ke **GitHub Container Registry (`ghcr.io`)** menggunakan penandaan tag Commit SHA (`${{ github.sha }}`).

---

## 6. Checklist Bukti Laporan Resmi (PDF)

| No | Komponen Bukti | Kriteria & Deskripsi Tangkapan Layar (Screenshot) |
| :-: | :--- | :--- |
| 1 | **Branch Protection** | Screenshot menu `Settings > Branches` menampilkan aturan proteksi branch `main` & `dev` |
| 2 | **Tautan Repositori** | Tautan resmi repositori publik: `https://github.com/AcademyCity-RND/uts-pl-24-542159-sv-25009-v1` |
| 3 | **Postman Collection** | Screenshot eksekusi koleksi Postman yang tersimpan di folder `/postman` |
| 4 | **CI/CD Pipeline** | Screenshot tab GitHub Actions menampilkan alur CI & CD yang berjalan sukses (hijau) |
| 5 | **GitHub Packages (GHCR)** | Screenshot halaman Packages menampilkan image Backend dan Frontend ber-tag Commit SHA |
| 6 | **Output Docker Compose** | Screenshot keluaran CLI `docker compose ps` menampilkan 3 service (DB, BE, FE) berstatus `Up` |
| 7 | **Log FrankenPHP** | Screenshot log container backend (`docker compose logs backend`) bukti server FrankenPHP |
| 8 | **Arsitektur Alur Data** | Diagram dan penjelasan naratif aliran data (Vue.js $\rightarrow$ Nginx $\rightarrow$ FrankenPHP $\rightarrow$ MySQL) |
| 9 | **Kendala & Resolusi** | Narasi kendala teknis yang dihadapi selama pengerjaan beserta solusi teknisnya |
