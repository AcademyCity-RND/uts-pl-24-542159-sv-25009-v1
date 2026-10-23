# 📦 JejakBarang — Pusat Lost & Found Anti-Tenggelam
> **Arsitektur Decoupled Monorepo: Laravel 11 (FrankenPHP) & Vue 3 MPA dengan target CI/CD GitHub Actions & Multi-Container Docker Orchestration**
> *Tugas Ujian Tengah Semester (UTS) — Mata Kuliah Konstruksi dan Evolusi Perangkat Lunak (KEPL)*

---

## 👤 Identitas Mahasiswa
- **Nama**: Hilarius Christiano Avin Paliling
- **NIM**: 24/542159/SV/25009
- **Mata Kuliah**: Konstruksi dan Evolusi Perangkat Lunak (KEPL)
- **Kelas / Prodi**: D4 Teknologi Rekayasa Perangkat Lunak / SV UGM
- **Repositori Publik**: [https://github.com/AcademyCity-RND/uts-pl-24-542159-sv-25009-v1](https://github.com/AcademyCity-RND/uts-pl-24-542159-sv-25009-v1)

---

## 📚 Dokumen Acuan Proyek

Gunakan dokumen berikut sebagai sumber acuan sesuai area masing-masing:

| Dokumen | Peran |
| :--- | :--- |
| [`PRD.md`](./PRD.md) | Sumber acuan utama kebutuhan produk, aturan bisnis, arsitektur, endpoint API, state transition, schema, infrastruktur, dan matriks pengujian. |
| [`DesignGuidelineUIUX.md`](./DesignGuidelineUIUX.md) | Acuan Fase 3 untuk design system Teal/Amber, light/dark mode, komponen Vue, UX flow, responsif, aksesibilitas, dan kontrak integrasi frontend–backend. |
| [`backend/README.md`](./backend/README.md) | Panduan teknis dan operasional khusus backend Laravel. |
| [`frontend/README.md`](./frontend/README.md) | Panduan teknis dan operasional khusus frontend Vue. |
| [`postman/README.md`](./postman/README.md) | Panduan penggunaan koleksi dan environment Postman untuk verifikasi API. |

### Aturan sinkronisasi dokumen

- `PRD.md` dan `DesignGuidelineUIUX.md` wajib tetap selaras sebelum implementasi
  frontend dimulai.
- `PRD.md` menjadi sumber utama untuk aturan bisnis, status, endpoint, payload,
  response, dan perilaku backend.
- `DesignGuidelineUIUX.md` menjadi sumber utama untuk visual, UX,
  aksesibilitas, responsif, serta detail integrasi frontend.
- Jika ditemukan perbedaan, selaraskan kedua dokumen terhadap implementasi
  backend aktual terlebih dahulu. Jangan mengubah kontrak API hanya dari sisi
  frontend.
- README khusus backend, frontend, dan Postman tetap menjadi panduan
  operasional per area dan tidak menggantikan kedua dokumen acuan utama di
  atas.

---

## 🧭 Rencana Implementasi Frontend Fase 3

Rencana teknis berikut menjadi acuan implementasi agar frontend tetap
konsisten dengan [DesignGuidelineUIUX.md](./DesignGuidelineUIUX.md) dan
kontrak API pada [PRD.md](./PRD.md):

| Area | Rekomendasi |
| :--- | :--- |
| Framework | Vue 3 + Vite dengan Composition API dan konfigurasi multi-page entry |
| Bahasa | TypeScript, menggunakan `<script setup lang="ts">` |
| Komponen | Vue Single File Components (`.vue`) sebagai format utama |
| Styling | Tailwind CSS untuk utility dan layout, dikombinasikan dengan CSS variables untuk design tokens |
| Design tokens | `--color-*` untuk Teal/Amber, light/dark mode, status, dan timer; komponen tidak menulis hex secara langsung |
| State | Pinia untuk auth, user role, theme preference, dan state UI yang dibagikan |
| API | Axios instance terpusat dengan interceptor auth, error mapping, `VITE_API_URL`, dan `VITE_STORAGE_URL` |
| Arsitektur halaman | Vue 3 Multi-Page Application (MPA) dengan entry point halaman terpisah; akses halaman dilindungi melalui pemeriksaan autentikasi/role pada halaman terkait |
| Gambar | Helper `buildImageUrl(path)` tunggal; komponen tidak merangkai URL storage sendiri |
| Validasi | Validasi UI mengikuti kontrak backend, termasuk email `@gmail.com`, password confirmation, dan mapping `422 errors[field]` |
| Aksesibilitas | Target WCAG 2.1 AA, fokus keyboard terlihat, label status tidak hanya berbasis warna, alt text, dan reduced motion |

### Aturan penggunaan Tailwind dan TSX

- Tailwind CSS **direkomendasikan** untuk utility class, grid responsive,
  spacing, state hover/focus, dan layout.
- CSS variables tetap menjadi sumber nilai token agar light/dark mode dapat
  berubah tanpa mengganti class komponen.
- Vue SFC `.vue` tetap menjadi format utama karena template form, card, modal,
  dashboard, dan tabel lebih mudah dirawat dalam format tersebut.
- TSX/JSX didukung, tetapi hanya digunakan secara selektif untuk komponen yang
  membutuhkan render function atau struktur sangat dinamis. TSX bukan format
  utama seluruh aplikasi.
- Jangan mencampur token warna hardcoded di komponen dengan token guideline.
- Setiap komponen harus mempertahankan kontrak API: frontend mengirim
  `accepted` untuk menerima report; backend yang memicu status internal
  `resolve_requested`.

### Struktur frontend yang ditargetkan

```text
frontend/
├── pages/                 # Entry HTML MPA (index, login, register, report, admin)
├── src/
│   ├── assets/styles/       # CSS variables, Tailwind entry, global styles
│   ├── components/          # Card, badge, timer, modal, navbar, form controls
│   ├── composables/         # Theme, countdown, image URL, reusable UI logic
│   ├── entries/             # Bootstrap TypeScript untuk setiap entry HTML MPA
│   ├── services/            # Axios instance and API service modules
│   ├── stores/              # Pinia auth, user, theme, and UI state
│   ├── types/               # TypeScript API/resource/payload types
│   └── views/               # Reusable page-level components for MPA entry points
├── tailwind.config.ts
├── tsconfig.json
└── vite.config.ts
```

Implementasi dimulai dari fondasi token/theme dan API client, kemudian
komponen reusable, halaman publik, autentikasi, form laporan, alur resolusi,
dan terakhir dashboard admin. Setiap tahap harus divalidasi pada lebar
375px, 768px, dan 1280px serta pada mode terang dan gelap.

---

## 🧾 Daftar Soal & Penilaian

> **Catatan Bukti & Pengumpulan:**
> * Setiap soal/task yang diselesaikan wajib menyertakan **1 (satu) screenshot bukti** yang valid.
> * Bukti berupa tangkapan layar browser, CLI/terminal, tab GitHub Actions, log container, atau potongan kode relevan.
> * Seluruh bukti dikompilasi ke dalam dokumen **Laporan Resmi (PDF)** untuk diunggah ke Classroom / eLoK.

| No | Komponen Soal / Spesifikasi Evaluasi | Bobot Poin | Status | Bukti Screenshot Wajib |
| :-: | :--- | :-: | :-: | :--- |
| **1** | **Backend RESTful API (Laravel 11 & Sanctum)**<br>• Endpoint Auth: `POST /api/register`, `POST /api/login`, & `POST /api/logout` berbasis token Sanctum.<br>• Resource CRUD & Status: `lost_items` mendukung GET, POST, PUT (via spoofing), DELETE, dan PATCH status.<br>• Endpoint Laporan & Repost: `POST /api/items/{id}/report`, `POST /api/items/{id}/repost`, serta `GET`/`PATCH` `/api/item-reports` (khusus admin).<br>• Seluruh endpoint selain auth/public dilindungi middleware `auth:sanctum` (disediakan `GET /api/public/items` untuk browsing publik dengan field kontak *omitted*).<br>• Format request/response murni JSON dengan HTTP Status Code semantik (200, 201, 401, 403, 404, 409, 422, 429).<br>• Mendukung unggahan gambar (`multipart/form-data`) dengan validasi `mimes:jpg,jpeg,png`.<br>• Ekspor Postman Collection tersimpan di dalam folder `/postman`.<br>• 12 Feature Test wajib lulus berdasarkan Feature Testing Matrix di `PRD.md`. | **15** | [x] Selesai | **SS-02** (CLI `php artisan test` 12 Test Hijau) & **SS-03** (Request/Response Postman) |
| **2** | **Frontend MPA (Vue.js 3 & Axios)**<br>• Halaman Login & Register: menyimpan auth token di LocalStorage/Pinia & redirect otomatis.<br>• Axios Interceptor: otomatis menyematkan header `Authorization: Bearer <BEARER_TOKEN>`.<br>• Halaman manajemen CRUD lengkap: Dashboard Publik (Card Grid), Form Pelaporan Barang (Upload Foto), dan Dashboard Admin (Inbox Tab: Pending, Approved, Resolusi).<br>• UI interaktif Countdown Timer untuk masa aktif pasif 72 jam.<br>• URL API dinamis berbasis file konfigurasi `.env` (`VITE_API_URL`), tidak di-*hardcode*. | **10** | [x] Selesai | **SS-04** (Login), **SS-05** (Dashboard Publik), **SS-06** (Form Lapor), **SS-07** (Admin Inbox) |
| **3** | **Git Workflow, Branching & CI Pipeline**<br>• Repositori publik monorepo berisi folder `/backend` dan `/frontend`.<br>• Percabangan standar: `main`, `dev`, dan `feature/*` (Restriksi direct push, wajib via PR).<br>• Pasang Branch Protection pada `main` & `dev` (Wajib PR & Status Check CI Hijau).<br>• Workflow `.github/workflows/ci.yml` minimal 2 job: test BE (`php artisan test` dengan service MySQL) & build FE (`pnpm build`).<br>• Dokumentasi lengkap di root `README.md`, `PRD.md`, `/backend/README.md`, dan `/frontend/README.md`. | **15** | [x] Selesai | **SS-01** (Branch Protection) & **SS-11** (Tab GitHub Actions CI Hijau setelah pipeline aktif) |
| **4** | **Continuous Deployment (CD) ke GHCR**<br>• Job deploy hanya berjalan saat push/merge eksplisit ke branch `main`.<br>• Nilai sensitif (`APP_KEY`, `DB_PASSWORD`, dll) disimpan aman di GitHub Secrets.<br>• Membangun Docker Image BE & FE otomatis lalu push ke GitHub Container Registry (`ghcr.io`).<br>• Penandaan versi image menggunakan tag Commit SHA (`${{ github.sha }}`), bukan `latest`. | **15** | [ ] Belum | **SS-12** (Workflow CD Deploy Hijau) & **SS-13** (GitHub Packages GHCR) |
| **5** | **Containerization (Dockerfiles & Layer Caching)**<br>• `backend/Dockerfile`: Base image FrankenPHP bertag versi spesifik (`dunglas/frankenphp:1.4-php8.3-bookworm`), layer caching dependensi Composer sebelum kode sumber.<br>• `frontend/Dockerfile`: Multi-stage build (Stage 1 kompilasi Node.js $\rightarrow$ Stage 2 serving Nginx ultra-ringan tanpa `node_modules`).<br>• Berkas `.dockerignore` di masing-masing folder.<br>• Bebas dari kredensial/rahasia yang tertulis mentah (*hardcoded*). | **20** | [x] Selesai | **SS-08** (Build Image Docker) & Potongan Dockerfile BE/FE |
| **6** | **Multi-container Orchestration (Docker Compose)**<br>• Berkas `docker-compose.yml` di root repo yang dapat dijalankan dengan 1 perintah: `docker compose up -d --build`.<br>• Minimal 3 service: Database (MySQL), Backend Laravel, dan Frontend Vue.<br>• Komunikasi antar-service menggunakan nama service internal Docker Compose (bukan IP laptop).<br>• Persistensi database dijamin dengan *Named Volume* (`db_data`) dan persistensi foto barang dengan `backend_storage`.<br>• Kredensial DB dibaca dari `.env` lokal (tidak di-commit), sertakan `.env.example`. | **25** | [x] Selesai | **SS-08** (CLI `docker compose ps`) & **SS-10** (Persistensi Data DB/Storage) |
| **7** | **Bonus Nilai: Application Server FrankenPHP**<br>• Menjalankan backend Laravel memakai **FrankenPHP** menggantikan PHP-FPM / Nginx / `artisan serve`.<br>• Base image bertag versi jelas dan terintegrasi di `docker-compose.yml`.<br>• Bukti bahwa request API benar-benar dilayani oleh runtime FrankenPHP. | **10** *(Bonus)* | [x] Selesai | **SS-09** (Log Container Backend `docker compose logs backend`) |
| **8** | **Penyusunan & Pengumpulan Laporan Resmi (PDF)**<br>• Dokumen laporan terstruktur memuat langkah pengerjaan, seluruh screenshot bukti soal 1–7 (SS-01 s.d. SS-14), link repositori, Postman collection, output `docker compose ps`, penjelasan alur data (Vue $\rightarrow$ Nginx $\rightarrow$ Laravel $\rightarrow$ DB), serta narasi kendala teknis dan solusinya.<br>• Diunggah ke Classroom / eLoK sesuai tenggat waktu. | **0** *(Instruksi)* | [x] Selesai | Berkas Laporan PDF Final Terunggah |

---

## 📊 Rekapitulasi Poin Penilaian
* **Total Poin Soal Utama (1–6)**: **100 Poin**
* **Poin Bonus (7)**: **10 Poin**
* **Total Nilai Maksimal yang Dapat Diraih**: **110 Poin**

---

## 📸 Matriks & Panduan Pengambilan Screenshot Laporan (Panduan Mahasiswa)

Gunakan tabel ini sebagai **lembar panduan mandiri (*cheatsheet*)** saat menyusun dokumen laporan PDF. Pastikan setiap titik tangkapan layar memuat elemen visual kunci yang disyaratkan:

| Kode SS | Nama Bukti & Soal Terkait | Lokasi Pengambilan (URL / CLI) | Elemen Kunci yang Wajib Terlihat | Status Bukti |
| :---: | :--- | :--- | :--- | :---: |
| **SS-01** | **Branch Protection Rules**<br>*(Soal 3)* | GitHub Web $\rightarrow$ `Settings > Branches` (atau `Rulesets`) | Nama branch `main` & `dev`, centang *Require a pull request before merging*, dan centang *Require status checks to pass*. | [x] Sudah Diambil |
| **SS-02** | **Automated Feature Tests**<br>*(Soal 1)* | Terminal / CLI: `php artisan test` (atau via Docker) | Tampilan hijau bertuliskan eksekusi seluruh 12 skenario Feature Test lulus (`PASS tests/Feature/...`), ringkasan *tests passed* (12) dan *assertions passed*. | [x] Selesai |
| **SS-03** | **Postman API Verification**<br>*(Soal 1)* | Aplikasi Postman (Koleksi "JejakBarang API" - 15 operasi endpoint, 18 request) | Koleksi memuat seluruh operasi endpoint (15 operasi endpoint, 18 request). Tangkapan layar menampilkan request & response representatif mencakup kode status `200`, `201`, `401`, `403`, `404`, `409`, `422`, dan `429`, serta Header `Authorization: Bearer <BEARER_TOKEN>`. | [x] Selesai |
| **SS-04** | **Frontend Login & Token State**<br>*(Soal 2)* | Browser: `http://localhost/login` + DevTools (F12) | Form login terisi, tombol submit, dan tab *Application > Local Storage* memperlihatkan token autentikasi tersimpan. | [x] Selesai |
| **SS-05** | **Dashboard Publik (Guest View)**<br>*(Soal 2)* | Browser: `http://localhost/` (Mode Incognito/Tanpa Login) | Grid kartu barang hilang/ditemukan yang berstatus `approved`, gambar barang, badge kategori, dan komponen *Countdown Timer* aktif. | [x] Selesai |
| **SS-06** | **Form Pelaporan Barang (User)**<br>*(Soal 2)* | Browser: `http://localhost/report` (User Login) | Form pengisian data barang, input file foto barang, dan notifikasi sukses submit (status barang masuk ke `pending`). | [x] Selesai |
| **SS-07** | **Dashboard Inbox Admin**<br>*(Soal 2)* | Browser: `http://localhost/admin` (Admin Login) | Tiga tab inbox (*Laporan Pending*, *Laporan Aktif Publik*, *Pesan Resolusi*) beserta tombol aksi (ACC, Tolak, Resolve). | [x] Selesai |
| **SS-08** | **Output CLI Docker Compose**<br>*(Soal 6)* | Terminal / CLI: `docker compose ps` | Daftar 3 service aktif (`database`, `backend`, `frontend`) dengan status `Up / running` dan pemetaan port host (`80`, `8000`, `3306`). | [x] Sudah Diambil |
| **SS-09** | **Log Runtime FrankenPHP**<br>*(Soal 7 - Bonus)* | Terminal / CLI: `docker compose logs backend` | Banner resmi FrankenPHP / Caddy Server dan baris log akses HTTP (`"GET /api/public/items HTTP/1.1" 200`) yang dilayani langsung oleh FrankenPHP. | [x] Sudah Diambil |
| **SS-10** | **Persistensi Data (Named Volumes)**<br>*(Soal 6)* | Terminal: `docker compose down` $\rightarrow$ `up -d` lalu cek Web | Bukti bahwa barang yang sudah diinput dan foto yang diunggah tetap tampil utuh di browser setelah container di-*down* dan dihidupkan ulang. | [x] Sudah Diambil |
| **SS-11** | **Tab GitHub Actions (CI Hijau)**<br>*(Soal 3)* | GitHub Web $\rightarrow$ Tab **Actions** | Daftar workflow run pada branch `dev` atau `feature/*` yang menunjukkan kedua job (Backend Test & Frontend Build) berhasil hijau/centang. | [x] Sudah Diambil |
| **SS-12** | **GitHub Actions CD Deployment**<br>*(Soal 4)* | GitHub Web $\rightarrow$ Tab **Actions** (Push to `main`) | Detail workflow run di branch `main` yang memperlihatkan job Deploy sukses membangun dan mendorong image ke GHCR. | [ ] Belum |
| **SS-13** | **GitHub Packages (GHCR Images)**<br>*(Soal 4)* | GitHub Web $\rightarrow$ Tab **Packages** | Terdaftar 2 image kontainer (`uts-pl-.../backend` dan `uts-pl-.../frontend`) dengan tag versi **Commit SHA** (bukan *latest*). | [ ] Belum |
| **SS-14** | **Pull Request & Merge History**<br>*(Soal 3)* | GitHub Web $\rightarrow$ Tab **Pull Requests** (Closed/Merged) | Halaman PR dari `feature/*` ke `dev` dan dari `dev` ke `main` memperlihatkan status checks lulus sebelum proses merge disetujui. | [ ] Belum |

---

## 📌 Status Fitur & Roadmap Pengerjaan Step-by-Step

### ✅ Fase 1: Setup Repository & Git Flow (Selesai)
*Fondasi Version Control, Standar Branching, dan Proteksi Repositori*
- [x] Inisialisasi repositori publik `uts-pl-24-542159-sv-25009-v1`.
- [x] Pembuatan struktur branch awal: `main`, `dev`, dan `feature/backend-api`.
- [x] Konfigurasi *Branch Protection Rules* pada GitHub (`main` & `dev`: require PR & status checks).
- [x] Dokumentasi acuan teknis: Penyusunan `PRD.md` dan panduan monorepo.
- [x] 📸 **Task Dokumentasi SS-01**: Ambil screenshot menu `Settings > Branches` (atau `Rulesets`) yang memperlihatkan proteksi pada `main` dan `dev`.

### ✅ Fase 2: Backend Development (Laravel & FrankenPHP Setup - Selesai)
*Pembangunan RESTful API, Autentikasi Sanctum, dan Pengujian Otomatis*
- [x] Setup Laravel 11 dan instalasi paket `laravel/sanctum`.
- [x] Rancang skema database & index: Migrasi tabel `users`, tabel `lost_items` (+composite index `status, approved_at`), dan tabel `item_reports` (+composite index `lost_item_id, status`).
- [x] Pembuatan Model Eloquent `User`, `LostItem`, dan `ItemReport` dengan proteksi *Mass Assignment* (`$guarded`).
- [x] FormRequest Validasi & Rate Limiting: `RegisterRequest`, `LoginRequest`, `StoreLostItemRequest`, `UpdateLostItemRequest`, dan `StoreItemReportRequest`.
- [x] Implementasi endpoint autentikasi: `POST /api/register` (role hardcode `user`), `POST /api/login`, dan `POST /api/logout`.
- [x] Implementasi rute publik `GET /api/public/items` (khusus status `approved` aktif < 72 jam, field kontak *omitted*).
- [x] Implementasi rute CRUD terproteksi (`auth:sanctum`): `GET /api/items`, `GET /api/items/{id}`, `POST /api/items`, `POST /api/items/{id}` (dengan `_method: PUT`), `DELETE /api/items/{id}`, dan `PATCH /api/items/{id}/status`.
- [x] Implementasi alur resolusi & laporan pihak ketiga: `POST /api/items/{id}/report` (anti-duplicate pending), `POST /api/items/{id}/repost`, serta `GET`/`PATCH` `/api/item-reports` khusus Admin dengan *Dual Pessimistic Locking* (`lockForUpdate()`).
- [x] Implementasi `LostItemPolicy` dan `ItemReportPolicy` untuk otorisasi akses (Respons 403 Forbidden).
- [x] Penulisan 12 Feature Test otomatis berbasis MySQL testing engine sesuai Feature Testing Matrix `PRD.md`.
- [x] Konfigurasi runner server aplikasi FrankenPHP.
- [x] Ekspor Postman Collection (15 operasi endpoint, 18 request) & Environment ke folder `/postman`.
- [x] 📸 **Task Dokumentasi SS-02**: Eksekusi perintah `php artisan test` di terminal hingga seluruh 12 Feature Test tampil hijau (*PASS*).
- [x] 📸 **Task Dokumentasi SS-03**: Jalankan request di Postman (Auth, CRUD, Report, Repost) dan ambil tangkapan layar response code & payload JSON-nya.

### ✅ Fase 3: Frontend Development (Vue.js MPA - Selesai)
*Antarmuka Pengguna MPA, Interceptor Axios, dan State Management*
- [x] Inisialisasi project Vue 3 + Vite dengan TypeScript dan Tailwind CSS di direktori /frontend.
- [x] Setup Axios instance dengan Global Interceptor (Authorization: Bearer <BEARER_TOKEN>) & penanganan error semantik serta jaringan (503/Offline).
- [x] Konfigurasi dinamis target API via .env (VITE_API_URL dan VITE_STORAGE_URL).
- [x] Pembuatan komponen modular: CardItem.vue, StatusBadge.vue, CountdownTimer.vue, BaseModal.vue, Navbar.vue.
- [x] Halaman Login & Registrasi (penyimpanan token di localStorage & auto-redirect).
- [x] Dashboard Publik (Guest View: Card Grid barang hilang/ditemukan yang disetujui admin).
- [x] Halaman Pelaporan Barang (User: form submit FormData foto barang, status awal pending).
- [x] Dashboard Admin (3 Tab Inbox: Laporan Pending, Laporan Aktif Publik, Pesan Resolusi).
- [x] 📸 **Task Dokumentasi SS-04**: Buka peramban di http://localhost:5173/login, buka F12 Console/Application Local Storage, dan ambil screenshot saat berhasil login.
- [x] 📸 **Task Dokumentasi SS-05**: Buka peramban di mode Incognito/Guest di http://localhost:5173/ dan screenshot tampilan Card Grid + Countdown Timer aktif.
- [x] 📸 **Task Dokumentasi SS-06**: Screenshot pengisian form pelaporan barang di http://localhost:5173/report beserta pemilihan foto barang.
- [x] 📸 **Task Dokumentasi SS-07**: Screenshot halaman http://localhost:5173/admin menampilkan 3 tab inbox dan tombol approval.

### ✅ Fase 4: Containerization & Orchestration (Selesai)
*Bungkus Layanan dengan Docker & Orkestrasi Terpadu*
- [x] Pembuatan `backend/Dockerfile` (Base image `dunglas/frankenphp:1.4-php8.3-bookworm` & layer caching composer).
- [x] Pembuatan `frontend/Dockerfile` (Multi-stage build: Node.js 22 builder $\rightarrow$ Nginx 1.27 Alpine).
- [x] Konfigurasi `nginx.conf` di frontend untuk melayani entry point MPA dan reverse proxy rute `/api/` serta `/storage/`.
- [x] Pembuatan `docker-compose.yml` di root (Service: `database`, `backend`, `frontend`, dan Named Volumes: `db_data` & `backend_storage`).
- [x] Pengujian integrasi lokal `docker compose up -d --build`.
- [x] 📸 **Task Dokumentasi SS-08**: Jalankan perintah `docker compose ps` di terminal dan ambil screenshot tabel 3 service yang berstatus `Up`.
- [x] 📸 **Task Dokumentasi SS-09**: Jalankan `docker compose logs backend` dan ambil screenshot log runtime FrankenPHP yang melayani HTTP request.
- [x] 📸 **Task Dokumentasi SS-10**: Jalankan `docker compose down`, jalankan kembali `docker compose up -d`, lalu refresh browser untuk membuktikan data dan foto barang tetap utuh tersimpan (Named Volume proof).

### 🚀 Fase 5: CI/CD Pipeline Implementation (80% Selesai)
*Otomatisasi Pengujian, Build, dan Publikasi Container ke GHCR*
- [x] Konfigurasi kredensial aman pada GitHub Secrets (`APP_KEY`, `DB_PASSWORD`, dll).
- [x] Penyusunan alur kerja `.github/workflows/ci.yml`:
  - Job CI (Backend Tests `php artisan test` & Frontend Build `pnpm build`) pada PR dan branch non-production.
  - Job CD (Docker Build & Push ke `ghcr.io` dengan tag Commit SHA) khusus push/merge ke `main`.
- [x] Verifikasi workflow CI berjalan sukses (centang hijau - 59 test cases ALL PASS).
- [x] 📸 **Task Dokumentasi SS-11**: Tab GitHub Actions CI centang hijau (seluruh pengujian backend & frontend build berhasil - Run #39884793369).
- [ ] 📸 **Task Dokumentasi SS-12**: Screenshot workflow push pada branch `main` yang memperlihatkan job Deploy ke GHCR berhasil sukses.
- [ ] 📸 **Task Dokumentasi SS-13**: Buka halaman **GitHub Packages** di repositori dan screenshot daftar image Backend & Frontend dengan tag Commit SHA.

### ⏳ Fase 6: Finalisasi & Penulisan Laporan
*Penyelarasan Akhir dan Kompilasi Bukti Akademik*
- [ ] Penggabungan (*merge*) seluruh branch fitur ke `dev` lalu ke `main` via Pull Request.
- [ ] 📸 **Task Dokumentasi SS-14**: Ambil screenshot riwayat PR yang berhasil di-merge dengan status checks lulus.
- [ ] Finalisasi dokumentasi: Root `README.md`, `PRD.md`, `DesignGuidelineUIUX.md`, `backend/README.md`, `frontend/README.md`, dan `postman/README.md`.
- [ ] Penyusunan dokumen Laporan Resmi (PDF) memuat 14 tangkapan layar bukti, penjelasan alur data, kendala teknis, dan resolusi solusinya.

---

## 🏗️ Arsitektur Sistem & Alur Data

```text
[ Browser Klien (Guest / User / Admin) ]
        │  (HTTP / Request UI ke Port 80)
        ▼
┌────────────────────────────────────────────────────────┐
│  Service 'frontend' (Nginx 1.27-Alpine)                │
│  • Melayani entry point dan berkas statis Vue 3 MPA     │
│  • Reverse Proxy: Meneruskan rute /api/* ke backend    │
│  • Reverse Proxy: Meneruskan rute /storage/* ke backend│
└───────────────────────┬────────────────────────────────┘
                        │  (Internal Docker Network: 'backend:80')
                        ▼
┌────────────────────────────────────────────────────────┐
│  Service 'backend' (FrankenPHP / PHP 8.3)              │
│  • Laravel 11 RESTful API + Laravel Sanctum            │
│  • Validasi FormRequest, Middleware Auth, Logic        │
│  • Penyimpanan Gambar di /app/storage/app/public       │
└───────────────────────┬────────────────────────────────┘
                        │  (Internal Docker Network: 'database:3306')
                        ▼
┌────────────────────────────────────────────────────────┐
│  Service 'database' (MySQL 8.x)                        │
│  • Penyimpanan Relasional (Tabel users, lost_items)    │
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
│   ├── app/                     # Controller, Model, Request, Policy, Middleware
│   ├── bootstrap/               # Inisialisasi aplikasi Laravel
│   ├── config/                  # Konfigurasi sistem
│   ├── database/                # Migrasi tabel users & lost_items, Seeder
│   ├── routes/                  # Definisi rute API (public & auth:sanctum)
│   ├── tests/                   # Automated Feature Tests (Auth & CRUD)
│   ├── Dockerfile               # Production container berbasis FrankenPHP
│   ├── .dockerignore            # Pengecualian build Docker backend
│   └── README.md                # Dokumentasi spesifik Backend
├── frontend/                    # Layanan Multi-Page Application (MPA) Vue.js 3
│   ├── public/                  # Aset publik statis
│   ├── src/                     # Source code Vue (Pages, Components, Axios)
│   ├── nginx.conf               # Konfigurasi Nginx (MPA assets, proxy /api/ & /storage/)
│   ├── Dockerfile               # Multi-stage container (Node.js -> Nginx)
│   ├── .dockerignore            # Pengecualian build Docker frontend
│   └── README.md                # Dokumentasi spesifik Frontend
├── postman/                     # Koleksi Pengujian API Postman
│   ├── uts-pl-api.postman_collection.json    # Request collection lengkap
│   ├── uts-pl-local.postman_environment.json # Variabel lingkungan lokal
│   └── README.md                             # Panduan pengujian & automasi token Postman
├── PRD.md                       # Dokumen pegangan resmi Product Requirements Document
├── DesignGuidelineUIUX.md       # Acuan desain UI/UX dan kontrak integrasi Fase 3
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

# 4. Buat symlink storage agar file gambar yang diunggah dapat diakses publik
docker compose exec backend php artisan storage:link
```
Akses layanan melalui peramban:
- **Frontend MPA (Web UI)**: `http://localhost`
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
* **`docs`**: Penambahan atau pembaruan berkas dokumentasi (README, PRD, panduan API).
* **`ci`**: Penambahan atau perubahan pada pipeline CI/CD GitHub Actions (`.github/workflows/`).
* **`refactor`**: Restrukturisasi kode tanpa mengubah perilaku eksternal sistem.
* **`test`**: Penambahan atau perbaikan unit test / feature test otomatis.

### 3. Ketentuan Penulisan
1. **Dilarang** menggunakan pesan commit ambigu satu kata (contoh: *"update"*, *"fix bug"*, *"coba"*).
2. Setiap commit wajib memiliki judul yang jelas dan dilanjutkan dengan **rincian daftar (*bullet list*)** pada *body* pesan commit untuk mempermudah peninjauan (*code review*) dan bukti di laporan.
