# 📦 JejakBarang — Pusat Informasi Lost & Found Kampus

**Ujian Tengah Semester (UTS) — Konstruksi dan Evolusi Perangkat Lunak 2026**  
**Hilarius Christiano Avin Paliling (24/542159/SV/25009)**  
*Program Studi Sarjana Terapan Rekayasa Perangkat Lunak, Departemen TEDI, Sekolah Vokasi, Universitas Gadjah Mada*  
*Repositori Publik:* [https://github.com/AcademyCity-RND/uts-pl-24-542159-sv-25009-v1](https://github.com/AcademyCity-RND/uts-pl-24-542159-sv-25009-v1)

---

## 📌 Ikhtisar Proyek

**JejakBarang** adalah platform *client-server* terpadu untuk pengelolaan pengumuman barang hilang dan ditemukan (*Lost & Found*) di lingkungan kampus. Masalah utama yang diselesaikan adalah pengumuman barang di media sosial/grup chat yang sangat cepat **tenggelam** tertimbun pesan lain, sulit dicari, tidak terverifikasi keabsahannya, serta tidak jelas status akhirnya.

Sistem ini menerapkan alur kendali **"User Propose $\rightarrow$ Admin Approve"**, proteksi privasi nomor kontak (omitted untuk publik/tamu), masa aktif pasif 72 jam (*Time-to-Live*), pencegahan klaim ganda (*Dual Pessimistic Locking* di level basis data MySQL), dan *HTTP Method Spoofing* untuk pembaruan foto barang. Seluruh layanan dibangun dengan arsitektur *Decoupled Monorepo* yang dikontainerisasi penuh menggunakan **Docker Compose** dan diotomatisasi melalui alur kerja **CI/CD GitHub Actions**.

---

## 📁 Struktur Repository

| Folder / Berkas | Isi & Peran Arsitektural |
| :--- | :--- |
| `backend/` | RESTful API murni berbasis **Laravel 11** & **FrankenPHP (PHP 8.3.36)**, autentikasi **Sanctum**, 59 automated test cases (MySQL suite), dan `Dockerfile`. |
| `frontend/` | Aplikasi Multi-Page Application (MPA) berbasis **Vue.js 3**, **Vite**, **TypeScript**, **Tailwind CSS**, **Pinia**, **Axios**, dan `Dockerfile` multi-stage (Node.js 22 builder $\rightarrow$ Nginx 1.27 runner). |
| `postman/` | Koleksi resmi pengujian API Postman (**15 operasi endpoint, 18 request**) beserta environment lokal terotomatisasi token Sanctum (`uts-pl-api.postman_collection.json`). |
| `.github/workflows/ci.yml` | Pipeline GitHub Actions untuk CI backend test (MySQL service container), CI frontend compilation, dan CD build/push image ke GitHub Container Registry (`ghcr.io`). |
| `docker-compose.yml` | Orkestrasi 3-tier container (`database`, `backend`, `frontend`) dengan isolasi bridge network dan 2 Named Volumes persisten. |
| `PRD.md` | *Product Requirements Document*: Acuan spesifikasi kebutuhan, aturan bisnis, matriks transisi status, kontrak endpoint API, dan skema basis data. |
| `DesignGuidelineUIUX.md` | Panduan desain UI/UX: Design token Teal/Amber, tipografi Inter, dual theming (light/dark), layout responsif, aksesibilitas WCAG AA, dan penanganan HTTP errors. |
| `roadmap.html` | Dashboard visual interaktif pelacakan progress tahapan UTS (Fase 1 s.d. Fase 6: 100% Selesai). |

---

## 🚀 Menjalankan dengan Docker Compose (Rekomendasi Utama)

Sistem akan otomatis mengatur jaringan bridge internal (`jejakbarang_network`) dan saling terhubung antar-container menggunakan **nama service internal Docker**, bukan alamat IP host/laptop.

### 1. Langkah Instalasi & Eksekusi:

1. **Siapkan berkas environment dari template:**
   ```bash
   cp .env.example .env
   ```
2. **Konfigurasikan nilai rahasia di berkas `.env`:**
   Pastikan variabel kunci terisi (Anda dapat membuat kunci aplikasi dengan perintah `php artisan key:generate --show` atau generator base64):
   ```env
   APP_KEY=base64:XwA9B9kZgH1+pD8x6a3S4D2eF7gH8jK1lM3nO5pQ7rS=
   DB_PASSWORD=uts_password
   DB_ROOT_PASSWORD=root_password
   ```
3. **Bangun dan jalankan seluruh container stack:**
   ```bash
   docker compose up -d --build
   ```
   *Catatan:* Skrip entrypoint backend (`docker-entrypoint.sh`) secara otomatis menunggu koneksi basis data MySQL berstatus *healthy*, menjalankan migrasi tabel (`php artisan migrate --force`), menyuntikkan seeder demo bergambar (`php artisan db:seed --force`), dan menghubungkan symlink berkas foto (`php artisan storage:link --force`).

---

### 🌐 Akses Layanan Aplikasi:

* **Frontend Web UI (MPA):** Buka [http://localhost](http://localhost) (atau port `${FE_PORT:-80}`)
* **Backend REST API:** Buka [http://localhost:8000/api](http://localhost:8000/api) (atau melalui reverse proxy [http://localhost/api](http://localhost/api))
* **Dokumentasi Storage Foto:** Buka [http://localhost/storage/lost_items/...](http://localhost/storage/)

### 👥 Kredensial Akun Demo (Bawaan Seeder):

| Role | Akun Email | Kata Sandi | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@jejakbarang.test` | `password123` | Akses panel moderasi `/admin`, validasi approval barang, dan proses laporan resolusi. |
| **Mahasiswa (Owner)** | `mahasiswa@jejakbarang.test` | `password123` | Pelaporan barang `/report`, verifikasi resolusi klaim barang pribadi, dan repost barang. |
| **Mahasiswa (Penemu)**| `penemu@jejakbarang.test` | `password123` | Mengajukan laporan penemuan pihak ketiga atas barang hilang milik pengguna lain. |

### 💾 Manajemen Data & Persistensi:

* **Menghentikan sistem (Data & Foto Tetap Aman Tersimpan):**
  ```bash
  docker compose down
  ```
* **Menghentikan sistem sekaligus menghapus seluruh volume basis data & foto:**
  ```bash
  docker compose down -v
  ```

---

### 🏗️ Arsitektur Service Docker

| Service | Base Image Resmi | Keterangan & Konfigurasi Volume |
| :--- | :--- | :--- |
| **`database`** | `mysql:8.0` | Basis data relasional MySQL. Persistensi data dijamin secara permanen melalui *Named Volume* **`db_data`** (`/var/lib/mysql`). Healthcheck aktif via `mysqladmin ping`. |
| **`backend`** | `dunglas/frankenphp:php8.3-bookworm` | Server aplikasi performa tinggi FrankenPHP. Menjalankan RESTful API Laravel 11. Terhubung ke service `database`. Persistensi foto barang dijamin via *Named Volume* **`backend_storage`** (`/app/storage/app/public`). |
| **`frontend`** | `nginx:1.27-alpine` | Web server Nginx ultra-ringan yang menyajikan artefak kompilasi Vue 3 MPA. Mengonfigurasikan *Reverse Proxy* transparan untuk rute `/api/` dan `/storage/` langsung ke `http://backend:80`. |

> **Alur Data Sistem:**  
> `Browser Klien` $\rightarrow$ `Frontend Nginx (Port 80)` $\rightarrow$ Reverse Proxy `/api/` & `/storage/` $\rightarrow$ `Backend FrankenPHP (Port 8000/80)` $\rightarrow$ `Database MySQL (Port 3306)`

---

## 💻 Menjalankan Tanpa Docker (Pengembangan Lokal)

Jika ingin menjalankan aplikasi langsung di mesin host pengembangan lokal:

### Terminal 1: Backend Service (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Pastikan konfigurasi database di .env mengarah ke MySQL lokal Anda
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve --port=8000
```

### Terminal 2: Frontend Service (Vue.js MPA)
```bash
cd frontend
cp .env.example .env
pnpm install
pnpm dev
```
Buka [http://localhost:5173](http://localhost:5173) untuk mengakses antarmuka pengguna mode pengembangan Vite.

---

## ⚡ Konfigurasi Server Aplikasi (FrankenPHP)

Backend JejakBarang tidak menggunakan stack tradisional PHP-FPM / Apache / `artisan serve`, melainkan server aplikasi modern **FrankenPHP** (`dunglas/frankenphp:php8.3-bookworm`) yang dibangun di atas web server Caddy dengan *Go-PHP core binding*.

### Verifikasi Server FrankenPHP:
```bash
# 1. Cek log aktivasi runtime FrankenPHP & ekstensi PHP 8.3
docker compose logs backend | Select-String "FrankenPHP"

# 2. Cek baris log akses HTTP request yang ditangani FrankenPHP
docker compose logs backend | Select-String "GET /api/public/items"

# 3. Cek respons HTTP header API
curl -I http://localhost:8000/api/public/items
# Server header akan mengembalikan: Server: Caddy / FrankenPHP
```

---

## 🐳 Desain Dockerfile & Layer Caching

Nilai rahasia (`APP_KEY`, kata sandi database) **tidak pernah di-hardcode** ke dalam Dockerfile, melainkan disuntikkan secara dinamis saat runtime container via environment variables / `.env`.

| Berkas | Strategi Build & Layer Caching |
| :--- | :--- |
| **`backend/Dockerfile`** | Menggunakan base image `dunglas/frankenphp:php8.3-bookworm` dengan `@composer`. Memanfaatkan **layer caching** Docker dengan menyalin `composer.json` dan `composer.lock` lebih dulu sebelum source code untuk mempercepat build ulang. |
| **`frontend/Dockerfile`** | Menggunakan arsitektur **multi-stage build**:  <br>• **Stage 1 (Builder):** `node:22-alpine` dengan pnpm 11.5.1 untuk instalasi dependensi dan kompilasi bundle Vite MPA.  <br>• **Stage 2 (Runner):** `nginx:1.27-alpine` menyajikan file statis dari `/app/dist` tanpa membawa direktori `node_modules`. |
| **`.dockerignore`** | Disediakan pada masing-masing folder (`backend/` dan `frontend/`) untuk mengecualikan direktori `vendor/`, `node_modules/`, `.git/`, dan berkas lokal `.env` agar image berukuran ringkas dan aman. |

---

## 🔄 Alur Kerja Git & CI/CD (GitHub Actions)

### 1. Git Branching & Protection Rules
* **Percabangan Standar:** `main` (kode stabil rilis produksi), `dev` (integrasi fitur pengembangan), dan `feature/*` (cabang per fitur).
* **Branch Protection:** Cabang `main` dan `dev` dilindungi aturan proteksi: **Dilarang direct push, wajib melalui Pull Request (PR), dan wajib lulus seluruh status checks CI**.

### 2. Pipeline Otomatisasi CI/CD (`.github/workflows/ci.yml`)

Pipeline GitHub Actions dirancang modular dengan memanfaatkan **GitHub Secrets** untuk menjaga keamanan kredensial:

| Nama Job | Tahapan Alur Kerja | Kondisi Pemicu |
| :--- | :--- | :--- |
| **`backend-test` (CI)** | Menyiapkan PHP 8.3, dependency caching Composer, inisialisasi basis data uji pada **MySQL 8.0 Service Container**, dan mengeksekusi `php artisan test`. <br>👉 **Hasil: 59 passed (248 assertions) - ALL GREEN ✓** | Push dan Pull Request ke `main`, `dev`, dan `feature/*` |
| **`frontend-build` (CI)** | Menyiapkan Node.js 22, aktivasi pnpm 11.5.1 via Corepack, caching store pnpm, dan kompilasi multi-page bundle `pnpm run build`. <br>👉 **Hasil: PASS in 12s ✓** | Push dan Pull Request ke `main`, `dev`, dan `feature/*` |
| **`deploy-ghcr` (CD)** | Login ke GitHub Container Registry (`ghcr.io`), sanitasi nama repositori ke lowercase standard OCI, pembangunan image container backend & frontend via Docker Buildx, dan push image ke registry. | **Hanya saat push / merge resmi ke branch `main`** |

### 🏷️ Format Publikasi Tag Image ke GHCR:
Image dipublikasikan ke GitHub Container Registry menggunakan **Commit SHA** (`${{ github.sha }}`) dan tag rilis spesifik (`1.0.0`), **bukan menggunakan tag `:latest`**:
* `ghcr.io/academycity-rnd/uts-pl-24-542159-sv-25009-v1-backend:<commit-sha>`
* `ghcr.io/academycity-rnd/uts-pl-24-542159-sv-25009-v1-backend:1.0.0`
* `ghcr.io/academycity-rnd/uts-pl-24-542159-sv-25009-v1-frontend:<commit-sha>`
* `ghcr.io/academycity-rnd/uts-pl-24-542159-sv-25009-v1-frontend:1.0.0`

---

## 🧾 Daftar Soal & Matriks Penilaian Evaluasi UTS

| No | Komponen Soal / Spesifikasi Evaluasi | Bobot | Status | Bukti Screenshot Wajib |
| :-: | :--- | :-: | :-: | :--- |
| **1** | **Backend RESTful API (Laravel 11 & Sanctum)**<br>• Auth: register, login, logout berbasis Sanctum Bearer Token.<br>• Resource CRUD `lost_items` (GET, POST, PUT spoofing, DELETE, PATCH status).<br>• Alur resolusi & repost pihak ketiga (`/items/{id}/report`, `/repost`, `/item-reports`).<br>• Proteksi middleware `auth:sanctum` & endpoint publik `GET /api/public/items` (kontak omitted).<br>• Format JSON semantik (200, 201, 401, 403, 404, 409, 422, 429).<br>• Unggah foto `multipart/form-data` validasi MIME gambar.<br>• Koleksi Postman 15 endpoint / 18 request lengkap.<br>• Pengujian otomatis 59 test cases (248 assertions) lulus 100%. | **15** | [x] Selesai | **SS-02** (CLI `php artisan test` Hijau) & **SS-03** (Request/Response Postman) |
| **2** | **Frontend MPA (Vue.js 3 & Axios)**<br>• Halaman Login & Register dengan penyimpanan token di LocalStorage/Pinia.<br>• Axios Interceptor otomatis menyematkan Bearer token & penanganan error.<br>• Dashboard Publik (Card Grid), Form Lapor Barang (Upload Foto), dan Dashboard Admin (3 Tab Inbox).<br>• UI interaktif Countdown Timer masa aktif pasif 72 jam.<br>• Target API dinamis berbasis `.env` (`VITE_API_URL`). | **10** | [x] Selesai | **SS-04** (Login), **SS-05** (Dashboard Publik), **SS-06** (Form Lapor), **SS-07** (Admin Inbox) |
| **3** | **Git Workflow, Branching & CI Pipeline**<br>• Repositori monorepo publik (`backend/` dan `frontend/`).<br>• Percabangan standar: `main`, `dev`, dan `feature/*` (Wajib PR).<br>• Branch Protection aktif pada `main` & `dev` (Require PR & CI Status Checks).<br>• Workflow `.github/workflows/ci.yml` mencakup backend test (MySQL) & frontend build (pnpm).<br>• Dokumentasi lengkap di `README.md`, `PRD.md`, `backend/README.md`, `frontend/README.md`. | **15** | [x] Selesai | **SS-01** (Branch Protection) & **SS-11** (Tab GitHub Actions CI Hijau) |
| **4** | **Continuous Deployment (CD) ke GHCR**<br>• Job deploy hanya berjalan saat push/merge resmi ke branch `main`.<br>• Kredensial sensitif disimpan aman di GitHub Secrets.<br>• Membangun Docker Image BE & FE otomatis lalu push ke `ghcr.io`.<br>• Penandaan versi image menggunakan tag Commit SHA (`${{ github.sha }}`) dan `1.0.0` (tanpa `:latest`). | **15** | [x] Selesai | **SS-12** (Workflow CD Deploy Hijau) & **SS-13** (GitHub Packages GHCR) |
| **5** | **Containerization (Dockerfiles & Layer Caching)**<br>• `backend/Dockerfile`: Base image FrankenPHP spesifik (`dunglas/frankenphp:php8.3-bookworm`), layer caching Composer.<br>• `frontend/Dockerfile`: Multi-stage build (Node.js 22 builder $\rightarrow$ Nginx 1.27 Alpine runner).<br>• Berkas `.dockerignore` di masing-masing folder.<br>• Bebas dari kredensial hardcoded. | **20** | [x] Selesai | **SS-08** (Build Image Docker) & Potongan Dockerfile BE/FE |
| **6** | **Multi-container Orchestration (Docker Compose)**<br>• Berkas `docker-compose.yml` di root repo yang dapat dijalankan dengan 1 perintah: `docker compose up -d --build`.<br>• 3 service utama: Database (MySQL 8.0), Backend Laravel (FrankenPHP), Frontend Vue (Nginx).<br>• Komunikasi antar-service memakai nama internal Docker.<br>• Persistensi database dijamin via *Named Volume* `db_data` dan foto via `backend_storage`.<br>• Kredensial dibaca dari `.env` lokal, disertakan `.env.example`. | **25** | [x] Selesai | **SS-08** (CLI `docker compose ps`) & **SS-10** (Persistensi Data DB/Storage) |
| **7** | **Bonus Nilai: Application Server FrankenPHP**<br>• Menjalankan backend Laravel memakai **FrankenPHP** menggantikan PHP-FPM / Nginx / `artisan serve`.<br>• Base image bertag versi jelas dan terintegrasi di `docker-compose.yml`.<br>• Bukti request API benar-benar dilayani oleh runtime FrankenPHP. | **10** *(Bonus)* | [x] Selesai | **SS-09** (Log Container Backend `docker compose logs backend`) |
| **8** | **Penyusunan & Pengumpulan Laporan Resmi (PDF)**<br>• Dokumen laporan terstruktur memuat langkah pengerjaan, seluruh screenshot bukti soal 1–7 (SS-01 s.d. SS-14), link repositori, Postman collection, output `docker compose ps`, penjelasan alur data, serta 25 narasi kendala teknis dan solusinya.<br>• Diunggah ke Classroom / eLoK sesuai tenggat waktu. | **0** *(Instruksi)* | [x] Selesai | Berkas Laporan PDF Final Terunggah |

### 📊 Rekapitulasi Nilai:
* **Total Poin Soal Utama (1–6)**: **100 Poin**
* **Poin Bonus (7)**: **10 Poin**
* **Total Nilai Maksimal yang Diraih**: **110 / 110 Poin (100% Selesai)**

---

## 📸 Matriks Bukti Screenshot Laporan (SS-01 s.d. SS-14)

Seluruh 14 bukti tangkapan layar telah berhasil diambil dan divalidasi kelengkapannya untuk laporan akhir:

| Kode SS | Nama Bukti & Soal Terkait | Lokasi Pengambilan (URL / CLI) | Elemen Kunci yang Terlihat | Status Bukti |
| :---: | :--- | :--- | :--- | :---: |
| **SS-01** | **Branch Protection Rules** *(Soal 3)* | GitHub Web $\rightarrow$ `Settings > Branches` | Aturan proteksi `main` & `dev`, *Require a pull request*, *Require status checks to pass*. | [x] Sudah Diambil |
| **SS-02** | **Automated Feature Tests** *(Soal 1)* | Terminal: `php artisan test` (Docker/Local) | 59 test cases (248 assertions) tampil hijau (*ALL PASS*) di engine MySQL `uts_kepl_test`. | [x] Sudah Diambil |
| **SS-03** | **Postman API Verification** *(Soal 1)* | Aplikasi Postman (Koleksi 15 endpoint, 18 request) | Eksekusi request Auth, CRUD, Report, Repost beserta respon kode 200, 201, 401, 403, 404, 409, 422. | [x] Sudah Diambil |
| **SS-04** | **Frontend Login & Token State** *(Soal 2)* | Browser: `http://localhost/login` + DevTools (F12) | Form login terisi dan tab *Application > Local Storage* memperlihatkan token Sanctum tersimpan. | [x] Sudah Diambil |
| **SS-05** | **Dashboard Publik (Guest View)** *(Soal 2)* | Browser: `http://localhost/` (Mode Tamu/Guest) | Grid kartu barang `approved`, badge status, gambar barang, dan Countdown Timer 72 jam aktif. | [x] Sudah Diambil |
| **SS-06** | **Form Pelaporan Barang (User)** *(Soal 2)* | Browser: `http://localhost/report` (User Login) | Form pengisian data barang, preview upload foto barang, dan alert sukses submit (status `pending`). | [x] Sudah Diambil |
| **SS-07** | **Dashboard Inbox Admin** *(Soal 2)* | Browser: `http://localhost/admin` (Admin Login) | Tiga tab moderasi (*Laporan Pending*, *Laporan Aktif Publik*, *Pesan Resolusi*) dan tombol aksi approval. | [x] Sudah Diambil |
| **SS-08** | **Output CLI Docker Compose** *(Soal 6)* | Terminal: `docker compose ps` | Tabel 3 service (`database`, `backend`, `frontend`) berstatus `Up / running / healthy` dan pemetaan port. | [x] Sudah Diambil |
| **SS-09** | **Log Runtime FrankenPHP** *(Soal 7 - Bonus)* | Terminal: `docker compose logs backend` | Log banner resmi FrankenPHP / Caddy Server dan baris log akses HTTP (`"GET /api/public/items HTTP/1.1" 200`). | [x] Sudah Diambil |
| **SS-10** | **Persistensi Data (Named Volumes)** *(Soal 6)* | Terminal: `docker compose down` $\rightarrow$ `up -d` | Data barang dan foto tetap utuh tampil di browser setelah container dimatikan dan dinyalakan ulang. | [x] Sudah Diambil |
| **SS-11** | **Tab GitHub Actions (CI Hijau)** *(Soal 3)* | GitHub Web $\rightarrow$ Tab **Actions** (PR/dev) | Workflow run CI menunjukkan job Backend Test (MySQL) & Frontend Build (pnpm) centang hijau. | [x] Sudah Diambil |
| **SS-12** | **GitHub Actions CD Deployment** *(Soal 4)* | GitHub Web $\rightarrow$ Tab **Actions** (Push to `main`) | Workflow run CD push ke `main` sukses mengeksekusi job deploy image ke GHCR (Run #39889656553). | [x] Sudah Diambil |
| **SS-13** | **GitHub Packages (GHCR Images)** *(Soal 4)* | GitHub Web $\rightarrow$ Tab **Packages** | Daftar 2 image (`backend` dan `frontend`) dengan tag Commit SHA dan tag `1.0.0` di `ghcr.io`. | [x] Sudah Diambil |
| **SS-14** | **Pull Request & Merge History** *(Soal 3)* | GitHub Web $\rightarrow$ Tab **Pull Requests** (Closed) | Riwayat PR #7 (`dev`) dan PR #8 (`main`) berhasil di-merge dengan seluruh status checks lulus. | [x] Sudah Diambil |

---

## 📌 Status Fitur & Roadmap Pengerjaan Step-by-Step

### ✅ Fase 1: Setup Repository & Git Flow (Selesai 100%)
- [x] Inisialisasi repositori monorepo publik `uts-pl-24-542159-sv-25009-v1`.
- [x] Pembuatan struktur cabang standar: `main`, `dev`, dan `feature/*`.
- [x] Penerapan *Branch Protection Rules* pada GitHub (`main` & `dev`: require PR & status checks).
- [x] Penyusunan dokumen panduan teknis: `PRD.md` dan struktur repositori.
- [x] 📸 **Task Dokumentasi SS-01**: Tangkapan layar menu *Settings > Branches* / *Rulesets*.

### ✅ Fase 2: Backend Development (Laravel 11 & FrankenPHP - Selesai 100%)
- [x] Setup Laravel 11 & instalasi paket `laravel/sanctum`.
- [x] Perancangan skema basis data & composite index tabel `users`, `lost_items`, dan `item_reports`.
- [x] Pembuatan Model Eloquent dengan proteksi *Mass Assignment* (`$guarded`).
- [x] FormRequest validasi input, MIME filter gambar, dan Rate Limiting kustom.
- [x] Implementasi endpoint autentikasi (`/register`, `/login`, `/logout`).
- [x] Rute publik `GET /api/public/items` (khusus status `approved` aktif < 72 jam, kontak *omitted*).
- [x] Rute CRUD terproteksi (`auth:sanctum`): GET, POST, PUT (spoofing), DELETE, dan PATCH status.
- [x] Implementasi alur resolusi dan laporan pihak ketiga dengan *Dual Pessimistic Locking* (`lockForUpdate()`).
- [x] Otorisasi berbasis hak akses menggunakan `LostItemPolicy` dan `ItemReportPolicy`.
- [x] Pengujian otomatis 59 test cases (248 assertions) pada basis data MySQL `uts_kepl_test`.
- [x] Konfigurasi server aplikasi FrankenPHP dan ekspor koleksi Postman (15 operasi endpoint, 18 request).
- [x] 📸 **Task Dokumentasi SS-02**: Eksekusi `php artisan test` hijau.
- [x] 📸 **Task Dokumentasi SS-03**: Eksekusi Postman API request & payload respons.

### ✅ Fase 3: Frontend Development (Vue.js 3 MPA - Selesai 100%)
- [x] Inisialisasi proyek Vue 3 + Vite dengan TypeScript dan Tailwind CSS di `/frontend`.
- [x] Setup Axios instance dengan Global Interceptor Bearer token & mapping error HTTP semantik.
- [x] Konfigurasi dinamis target API via `.env` (`VITE_API_URL` dan `VITE_STORAGE_URL`).
- [x] Pembuatan komponen modular: `CardItem.vue`, `StatusBadge.vue`, `CountdownTimer.vue`, `BaseModal.vue`, `Navbar.vue`.
- [x] Halaman Login & Registrasi terintegrasi API (penyimpanan token di LocalStorage & auto-redirect).
- [x] Dashboard Publik (Guest View: Card Grid barang approved aktif).
- [x] Halaman Pelaporan Barang (User: form submit FormData foto barang, status awal pending).
- [x] Dashboard Admin (3 Tab Moderasi: Laporan Pending, Laporan Aktif Publik, Pesan Resolusi).
- [x] 📸 **Task Dokumentasi SS-04**: Login & LocalStorage token DevTools.
- [x] 📸 **Task Dokumentasi SS-05**: Dashboard Publik mode Incognito/Tamu & Countdown Timer aktif.
- [x] 📸 **Task Dokumentasi SS-06**: Pengisian form pelaporan barang beserta unggah foto.
- [x] 📸 **Task Dokumentasi SS-07**: Dashboard Admin 3 tab inbox moderasi.

### ✅ Fase 4: Containerization & Orchestration (Selesai 100%)
- [x] Pembuatan `backend/Dockerfile` (Base image `dunglas/frankenphp:php8.3-bookworm` & layer caching).
- [x] Pembuatan `frontend/Dockerfile` (Multi-stage build: Node.js 22 builder $\rightarrow$ Nginx 1.27 Alpine runner).
- [x] Konfigurasi `nginx.conf` di frontend untuk serving MPA dan reverse proxy rute `/api/` serta `/storage/`.
- [x] Pembuatan `docker-compose.yml` di root (Service: `database`, `backend`, `frontend`, dan Named Volumes: `db_data` & `backend_storage`).
- [x] Pengujian integrasi lokal `docker compose up -d --build`.
- [x] 📸 **Task Dokumentasi SS-08**: Keluaran CLI `docker compose ps` (3 service status Up).
- [x] 📸 **Task Dokumentasi SS-09**: Log container backend bukti FrankenPHP melayani HTTP request.
- [x] 📸 **Task Dokumentasi SS-10**: Bukti persistensi data DB & storage foto setelah `docker compose down` $\rightarrow$ `up -d`.

### ✅ Fase 5: CI/CD Pipeline Implementation (Selesai 100%)
- [x] Konfigurasi kredensial aman pada GitHub Secrets (`APP_KEY`, `DB_PASSWORD`, dll).
- [x] Penyusunan alur kerja `.github/workflows/ci.yml`:
  - Job CI (Backend Tests `php artisan test` dengan MySQL Service Container & Frontend Build `pnpm build` Node 22).
  - Job CD (Docker Build & Push ke `ghcr.io` bertag Commit SHA & `1.0.0`) khusus trigger push/merge ke `main`.
- [x] Verifikasi workflow CI berjalan sukses (centang hijau - 59 test cases ALL PASS).
- [x] Publikasi image container ke GitHub Container Registry (`ghcr.io`) berhasil sukses.
- [x] 📸 **Task Dokumentasi SS-11**: Tab GitHub Actions CI centang hijau (Run #39884793369).
- [x] 📸 **Task Dokumentasi SS-12**: Alur kerja CD push branch `main` sukses deploy ke GHCR (Run #39889656553).
- [x] 📸 **Task Dokumentasi SS-13**: Halaman GitHub Packages menampilkan image backend dan frontend bertag Commit SHA dan `1.0.0`.

### ✅ Fase 6: Finalisasi & Penulisan Laporan (Selesai 100%)
- [x] Penggabungan (*merge*) seluruh branch fitur ke `dev` (PR #7) lalu rilis ke `main` (PR #8) dengan status checks lulus.
- [x] 📸 **Task Dokumentasi SS-14**: Tangkapan layar riwayat PR yang berhasil di-merge dengan status checks lulus.
- [x] Penyelarasan seluruh dokumentasi: Root `README.md`, `PRD.md`, `DesignGuidelineUIUX.md`, `backend/README.md`, `frontend/README.md`, `postman/README.md`, dan `roadmap.html`.
- [x] Penyusunan 25 catatan kendala teknis dan resolusi solusi akademik untuk disematkan pada Laporan Akhir (PDF).

---

## 📜 Standar & Aturan Pesan Commit (Conventional Commits)

Seluruh riwayat commit di repositori ini menerapkan standar **Conventional Commits** disertai rincian daftar perubahan (*bulleted list*):

```text
<type>(<scope>): <ringkasan singkat dalam kalimat aktif/imperatif>

- <detail perubahan spesifik 1>
- <detail perubahan spesifik 2>
- <alasan/konteks teknis jika relevan>
```

* **`feat`**: Penambahan fitur fungsional baru.
* **`fix`**: Perbaikan bug atau kegagalan eksekusi.
* **`chore`**: Pemeliharaan repositori, dependensi, atau konfigurasi dasar.
* **`docs`**: Penambahan atau pembaruan berkas dokumentasi.
* **`ci`**: Penambahan atau perubahan pada pipeline CI/CD GitHub Actions.
* **`refactor`**: Restrukturisasi kode tanpa mengubah perilaku sistem.
* **`test`**: Penambahan atau perbaikan unit test / feature test otomatis.
