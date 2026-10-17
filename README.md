# 📦 UTS Konstruksi dan Evolusi Perangkat Lunak — Decoupled Full-Stack Architecture
> **Aplikasi Monorepo Modern: Laravel 11 (FrankenPHP) & Vue 3 SPA dengan Otomatisasi CI/CD GitHub Actions & Multi-Container Docker Orchestration**

---

## 👤 Identitas Mahasiswa
- **Nama**: Hilarius Christiano Avin Paliling
- **NIM**: 24/542159/SV/25009
- **Mata Kuliah**: Konstruksi dan Evolusi Perangkat Lunak (KEPL)
- **Kelas / Prodi**: D4 Teknologi Rekayasa Perangkat Lunak / SV UGM
- **Repositori Publik**: [https://github.com/AcademyCity-RND/uts-pl-24-542159-sv-25009-v1](https://github.com/AcademyCity-RND/uts-pl-24-542159-sv-25009-v1)

---

## 🧾 Daftar Soal & Penilaian

> **Catatan Bukti & Pengumpulan:**
> * Setiap soal/task yang diselesaikan wajib menyertakan **1 (satu) screenshot bukti** yang valid.
> * Bukti berupa tangkapan layar browser, CLI/terminal, tab GitHub Actions, log container, atau potongan kode relevan.
> * Seluruh bukti dikompilasi ke dalam dokumen **Laporan Resmi (PDF)** untuk diunggah ke Classroom / eLoK.

| No | Komponen Soal / Spesifikasi Evaluasi | Bobot Poin | Status | Bukti Screenshot Wajib |
| :-: | :--- | :-: | :-: | :--- |
| **1** | **Backend RESTful API (Laravel 11 & Sanctum)**<br>• Endpoint Auth: `POST /api/login` & `POST /api/logout` berbasis token.<br>• Seluruh endpoint selain login dilindungi middleware `auth:sanctum`.<br>• Minimal 1 resource CRUD lengkap (GET all, GET one, POST, PUT, DELETE) tersimpan di DB.<br>• Format request/response murni JSON dengan HTTP Status Code semantik (200, 201, 401, 422).<br>• Ekspor Postman Collection tersimpan di dalam folder `/postman`.<br>• Minimal 2 Feature Test otomatis yang terverifikasi lulus saat `php artisan test`. | **15** | [ ] Belum | SS CLI `php artisan test` (Hijau) & SS Request/Response Postman |
| **2** | **Frontend SPA (Vue.js 3 & Axios)**<br>• Halaman Login yang menyimpan auth token di LocalStorage/Pinia & redirect otomatis.<br>• Axios Interceptor yang otomatis menyematkan header `Authorization: Bearer <token>`.<br>• Halaman manajemen CRUD lengkap (Tampil Data, Form Tambah, Form Ubah, Modal Hapus).<br>• URL API dinamis berbasis file konfigurasi `.env` (`VITE_API_URL`), tidak di-*hardcode*. | **10** | [ ] Belum | SS Tampilan Login, Tabel Data, Form Input, & Konfirmasi Hapus |
| **3** | **Git Workflow, Branching & CI Pipeline**<br>• Repositori publik monorepo berisi folder `/backend` dan `/frontend`.<br>• Percabangan standar: `main`, `dev`, dan `feature/*` (Restriksi direct push, wajib via PR).<br>• Pasang Branch Protection pada `main` & `dev` (Wajib PR & Status Check CI Hijau).<br>• Workflow `.github/workflows/ci.yml` minimal 2 job: test BE (`php artisan test`) & build FE (`npm ci && npm run build`).<br>• Dokumentasi lengkap di root `README.md`, `/backend/README.md`, dan `/frontend/README.md`. | **15** | [ ] Belum | SS Halaman Settings > Branches/Rules & SS Tab GitHub Actions (CI Sukses) |
| **4** | **Continuous Deployment (CD) ke GHCR**<br>• Job deploy hanya berjalan saat push/merge eksplisit ke branch `main`.<br>• Nilai sensitif (`APP_KEY`, `DB_PASSWORD`, dll) disimpan aman di GitHub Secrets.<br>• Membangun Docker Image BE & FE otomatis lalu push ke GitHub Container Registry (`ghcr.io`).<br>• Penandaan versi image menggunakan tag Commit SHA (`${{ github.sha }}`), bukan `latest`. | **15** | [ ] Belum | SS Workflow Run `main` (Job Deploy Sukses) & SS Halaman GitHub Packages |
| **5** | **Containerization (Dockerfiles & Layer Caching)**<br>• `backend/Dockerfile`: Base image dengan tag versi spesifik (bukan `latest`), layer caching dependensi `composer.json` & `composer.lock` sebelum kode sumber.<br>• `frontend/Dockerfile`: Multi-stage build (Stage 1 kompilasi Node.js $\rightarrow$ Stage 2 serving Nginx ultra-ringan tanpa `node_modules`).<br>• Berkas `.dockerignore` di masing-masing folder.<br>• Bebas dari kredensial/rahasia yang tertulis mentah (*hardcoded*). | **20** | [ ] Belum | Potongan `Dockerfile` BE & FE serta SS hasil build image Docker lokal |
| **6** | **Multi-container Orchestration (Docker Compose)**<br>• Berkas `docker-compose.yml` di root repo yang dapat dijalankan dengan 1 perintah: `docker compose up -d --build`.<br>• Minimal 3 service: Database (MySQL), Backend Laravel, dan Frontend Vue.<br>• Komunikasi antar-service menggunakan nama service internal Docker Compose (bukan IP laptop).<br>• Persistensi database dijamin dengan *Named Volume* (data aman saat `down` lalu `up -d`).<br>• Kredensial DB dibaca dari `.env` lokal (tidak di-commit), sertakan `.env.example`. | **25** | [ ] Belum | SS Terminal `docker compose ps` (3 service Up) & Bukti persistensi DB |
| **7** | **Bonus Nilai: Application Server FrankenPHP**<br>• Menjalankan backend Laravel memakai **FrankenPHP** menggantikan PHP-FPM / Nginx / `artisan serve`.<br>• Base image bertag versi jelas dan terintegrasi di `docker-compose.yml`.<br>• Bukti bahwa request API benar-benar dilayani oleh runtime FrankenPHP. | **10** *(Bonus)* | [ ] Belum | SS Log Container Backend (`docker compose logs backend`) runtime FrankenPHP |
| **8** | **Penyusunan & Pengumpulan Laporan Resmi (PDF)**<br>• Dokumen laporan terstruktur memuat langkah pengerjaan, seluruh screenshot bukti soal 1–7, link repositori, Postman collection, output `docker compose ps`, penjelasan alur data (Vue $\rightarrow$ Laravel $\rightarrow$ DB), serta narasi kendala teknis dan solusinya.<br>• Diunggah ke Classroom / eLoK sesuai tenggat waktu. | **0** *(Instruksi)* | [ ] Belum | Dokumen Laporan PDF Final Terunggah |

---

## 📊 Rekapitulasi Poin Penilaian
* **Total Poin Soal Utama (1–6)**: **100 Poin**
* **Poin Bonus (7)**: **10 Poin**
* **Total Nilai Maksimal yang Dapat Diraih**: **110 Poin**

---

## 📌 Status Fitur & Roadmap Pengerjaan Step-by-Step

### ⏳ Fase 1: Setup Repository & Git Flow
*Fondasi Version Control, Standar Branching, dan Proteksi Repositori*
- [ ] Inisialisasi repositori publik `uts-pl-24-542159-sv-25009-v1`.
- [ ] Pembuatan struktur branch awal: `main`, `dev`, dan `feature/setup`.
- [ ] Konfigurasi *Branch Protection Rules* pada GitHub (`main` & `dev`: require PR & status checks).
- [ ] *Pengambilan Screenshot*: Halaman `Settings > Branches / Rules`.

### ⏳ Fase 2: Backend Development (Laravel & FrankenPHP Setup)
*Pembangunan RESTful API, Autentikasi Sanctum, dan Pengujian Otomatis*
- [ ] Setup Laravel 11, instalasi & konfigurasi Laravel Sanctum.
- [ ] Rancang skema database (Resource CRUD), Migration, Seeder, Model, API Controller, dan FormRequest.
- [ ] Implementasi endpoint `POST /api/login`, `POST /api/logout`, dan CRUD resource terproteksi.
- [ ] Penulisan minimal 2 Feature Test otomatis (`AuthTest` & `ResourceCrudTest`).
- [ ] Integrasi runner FrankenPHP sebagai server aplikasi.
- [ ] Ekspor Postman Collection & Environment ke folder `/postman`.
- [ ] *Pengambilan Screenshot*: Eksekusi `php artisan test` & pengujian Postman.

### ⏳ Fase 3: Frontend Development (Vue.js Setup)
*Antarmuka Pengguna SPA, Interceptor Axios, dan State Management*
- [ ] Inisialisasi project Vue 3 + Vite di direktori `/frontend`.
- [ ] Konfigurasi Vue Router & Axios Client dengan HTTP Interceptor (`Authorization: Bearer <token>`).
- [ ] Konfigurasi dinamis target API via `.env` (`VITE_API_URL`).
- [ ] Pembangunan Halaman Login (penyimpanan token di `localStorage` & auto-redirect).
- [ ] Pembangunan Halaman CRUD (Tabel Data, Form Tambah, Form Edit, Konfirmasi Hapus).
- [ ] *Pengambilan Screenshot*: Halaman Login dan Halaman CRUD saat beroperasi.

### ⏳ Fase 4: Containerization & Orchestration
*Bungkus Layanan dengan Docker & Orkestrasi Terpadu*
- [ ] Pembuatan `backend/Dockerfile` (Base image FrankenPHP bertag versi & layer caching composer).
- [ ] Pembuatan `frontend/Dockerfile` (Multi-stage build: Node.js $\rightarrow$ Nginx Alpine).
- [ ] Konfigurasi `nginx.conf` di frontend untuk SPA routing (`try_files`) & reverse proxy API.
- [ ] Pembuatan `docker-compose.yml` di root (Service: `database`, `backend`, `frontend`, dan *Named Volume*).
- [ ] Pengujian integrasi lokal `docker compose up -d --build`.
- [ ] *Pengambilan Screenshot*: Output CLI `docker compose ps` & log FrankenPHP.

### ⏳ Fase 5: CI/CD Pipeline Implementation
*Otomatisasi Pengujian, Build, dan Publikasi Container ke GHCR*
- [ ] Konfigurasi kredensial aman pada GitHub Secrets (`APP_KEY`, `DB_PASSWORD`, dll).
- [ ] Penyusunan alur kerja `.github/workflows/ci.yml`:
  - Job CI (Backend Tests & Frontend Build) pada PR dan branch non-production.
  - Job CD (Docker Build & Push ke `ghcr.io` dengan tag Commit SHA) khusus push ke `main`.
- [ ] Verifikasi workflow berjalan sukses (hijau).
- [ ] *Pengambilan Screenshot*: Tab GitHub Actions (hijau) & halaman GitHub Packages (GHCR).

### ⏳ Fase 6: Finalisasi & Penulisan Laporan
*Penyelarasan Akhir dan Kompilasi Bukti Akademik*
- [ ] Penggabungan (*merge*) seluruh branch fitur ke `dev` lalu ke `main` via Pull Request.
- [ ] Finalisasi dokumentasi: Root `README.md`, `backend/README.md`, `frontend/README.md`, dan `postman/README.md`.
- [ ] Penyusunan dokumen Laporan Resmi (PDF) sesuai checklist instruksi Soal 8.

---

## 🏗️ Arsitektur Sistem & Alur Data

```text
[ Browser Klien ]
        │  (HTTP / Request UI ke Port 80)
        ▼
┌────────────────────────────────────────────────────────┐
│  Service 'frontend' (Nginx 1.27-Alpine)                │
│  • Melayani berkas statis Vue 3 SPA (HTML/JS/CSS)      │
│  • Reverse Proxy: Meneruskan rute /api/* ke backend    │
└───────────────────────┬────────────────────────────────┘
                        │  (Internal Docker Network: 'backend:80')
                        ▼
┌────────────────────────────────────────────────────────┐
│  Service 'backend' (FrankenPHP / PHP 8.3)              │
│  • Laravel 11 RESTful API + Laravel Sanctum            │
│  • Validasi FormRequest, Middleware Auth, Logic        │
└───────────────────────┬────────────────────────────────┘
                        │  (Internal Docker Network: 'database:3306')
                        ▼
┌────────────────────────────────────────────────────────┐
│  Service 'database' (MySQL 8.x)                        │
│  • Relational Database Storage                         │
│  • Persistensi Data melalui Docker Named Volume        │
└────────────────────────────────────────────────────────┘
```

---

## 📁 Struktur Monorepo (Struktur Direktori Lengkap)

```text
uts-pl-24-542159-sv-25009-v1/
├── .github/
│   └── workflows/
│       └── ci.yml               # Pipeline CI (Test & Build) dan CD (Build & Push GHCR)
├── backend/                     # Layanan RESTful API Laravel 11 & FrankenPHP
│   ├── app/                     # Controller, Model, Request, Middleware
│   ├── bootstrap/               # Inisialisasi aplikasi Laravel
│   ├── config/                  # Konfigurasi sistem
│   ├── database/                # Migrasi & Seeder
│   ├── routes/                  # Definisi rute API terproteksi
│   ├── tests/                   # Automated Feature Tests
│   ├── Dockerfile               # Production container berbasis FrankenPHP
│   ├── .dockerignore            # Pengecualian build Docker backend
│   └── README.md                # Dokumentasi spesifik Backend
├── frontend/                    # Layanan Single Page Application (SPA) Vue.js 3
│   ├── public/                  # Aset publik statis
│   ├── src/                     # Source code Vue (Views, Components, Router, Axios)
│   ├── nginx.conf               # Konfigurasi Nginx (SPA routing & reverse proxy)
│   ├── Dockerfile               # Multi-stage container (Node.js -> Nginx)
│   ├── .dockerignore            # Pengecualian build Docker frontend
│   └── README.md                # Dokumentasi spesifik Frontend
├── postman/                     # Koleksi Pengujian API Postman
│   ├── uts-pl-api.postman_collection.json    # Request collection lengkap
│   ├── uts-pl-local.postman_environment.json # Variabel lingkungan lokal
│   └── README.md                             # Panduan pengujian & automasi token Postman
├── docker-compose.yml           # Orkestrasi 3 service (Database, Backend, Frontend)
├── .env.example                 # Template variabel lingkungan Docker & Database
├── .gitignore                   # Pengecualian pelacakan Git
└── README.md                    # Dokumentasi utama proyek
```

---

## ⚡ Panduan Menjalankan Sistem (Quick Start)

### 1. Menjalankan via Docker Compose (Rekomendasi Utama)
Pastikan Docker Desktop / Engine telah menyala di perangkat Anda:
```bash
# 1. Salin konfigurasi environment dari template
cp .env.example .env

# 2. Bangun dan jalankan seluruh container stack di latar belakang
docker compose up -d --build

# 3. Jalankan migrasi dan seeder database awal di container backend
docker compose exec backend php artisan migrate --seed
```
Akses layanan melalui peramban:
- **Frontend SPA (Web UI)**: `http://localhost`
- **Backend API**: `http://localhost:8000` (atau via proxy `http://localhost/api`)

### 2. Menjalankan Pengujian Otomatis
```bash
docker compose exec backend php artisan test
```

---

## 📜 Standar & Aturan Pesan Commit (Git Commit Convention)

Untuk menjaga rekam jejak evolusi kode yang bersih, profesional, dan mudah ditelusuri (*audit trail*), seluruh riwayat commit di repositori ini menerapkan standar **Conventional Commits** disertai rincian daftar perubahan (*bulleted list*):

### 1. Format Pesan Commit
```text
<type>(<scope>): <ringkasan singkat dalam kalimat aktif/imperatif>

- <detail perubahan spesifik 1>
- <detail perubahan spesifik 2>
- <alasan/konteks teknis jika relevan>
```

### 2. Standar Tipe Commit (`type`)
* **`feat`**: Penambahan fitur fungsional baru (misal: rute API, controller, halaman Vue).
* **`fix`**: Perbaikan bug atau kegagalan eksekusi.
* **`chore`**: Pemeliharaan repositori, inisialisasi scaffold, struktur direktori, atau konfigurasi dasar.
* **`docs`**: Penambahan atau pembaruan berkas dokumentasi (README, panduan API, template laporan).
* **`ci`**: Penambahan atau perubahan pada pipeline CI/CD GitHub Actions (`.github/workflows/`).
* **`refactor`**: Restrukturisasi kode tanpa mengubah perilaku eksternal sistem.
* **`test`**: Penambahan atau perbaikan unit test / feature test otomatis.

### 3. Ketentuan Penulisan
1. **Dilarang** menggunakan pesan commit ambigu satu kata (contoh: *"update"*, *"fix bug"*, *"coba"*).
2. Setiap commit wajib memiliki judul yang jelas dan dilanjutkan dengan **rincian daftar (*bullet list*)** pada *body* pesan commit untuk mempermudah peninjauan (*code review*) dan bukti di laporan.
