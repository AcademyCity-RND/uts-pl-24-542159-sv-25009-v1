# 🎨 JejakBarang — Frontend Multi-Page Application

> **Layanan:** Frontend Multi-Page Application (MPA) untuk antarmuka pengguna
> **Teknologi:** Vue.js 3, Vite, TypeScript, Tailwind CSS, Axios, Pinia
> **Arsitektur:** Decoupled Monorepo Service

---

## 1. Ikhtisar Layanan

Layanan Frontend ini menyediakan antarmuka pengguna untuk sistem **JejakBarang**
(Pusat Informasi *Lost & Found* Kampus). Aplikasi dibangun menggunakan
**Vue.js 3** dengan Composition API, dikompilasi oleh **Vite** menggunakan
**pnpm**, dan diarahkan ke arsitektur **Multi-Page Application (MPA)** dengan
entry point halaman terpisah.

Frontend terintegrasi secara asinkron dengan RESTful API Backend Laravel 11
melalui **Axios**, menggunakan komponen modular, design token Teal/Amber,
dukungan tema Terang/Gelap, state management Pinia, dan penanganan galat
jaringan sesuai kontrak pada dokumen acuan.

> **Dokumen Acuan Terpadu:**
> - [PRD.md](../PRD.md): Aturan bisnis, kontrak endpoint API, state transition matrix, dan alur resolusi.
> - [DesignGuidelineUIUX.md](../DesignGuidelineUIUX.md): Spesifikasi desain UI/UX, token warna Teal/Amber, tipografi, komponen, layout responsif (mobile-first), aksesibilitas WCAG AA, dan penanganan status error.
> - [README.md (Root)](../README.md): Gambaran umum monorepo, daftar soal penilaian, dan matriks screenshot bukti (SS-04 s.d. SS-07).

---

## 📌 Status Implementasi Fitur Frontend (Fase 3)

- [x] **Step 3.1:** Inisialisasi Vue.js 3 + Vite + TypeScript + Tailwind CSS.
- [ ] **Step 3.2:** Setup Axios instance, interceptor autentikasi, dan mapping error HTTP.
- [ ] **Step 3.3:** Konfigurasi environment `VITE_API_URL` dan `VITE_STORAGE_URL`, termasuk helper URL gambar.
- [ ] **Step 3.4:** Pembuatan komponen UI reusable dan sistem tema light/dark.
- [ ] **Step 3.5:** Implementasi halaman login dan registrasi.
- [ ] **Step 3.6:** Implementasi dashboard publik dan countdown timer.
- [ ] **Step 3.7:** Implementasi form pelaporan barang dengan upload gambar.
- [ ] **Step 3.8:** Implementasi dashboard admin dan alur moderasi.
- [ ] **Step 3.9:** Implementasi alur resolusi dan repost.
- [ ] **Step Dokumentasi SS-04 s.d. SS-07:** Pengambilan screenshot bukti frontend.

---

## 1.1 Spesifikasi Tech Stack
- **Framework**: Vue.js 3 (`<script setup>` SFC)
- **Tooling / Bundler**: Vite
- **Arsitektur halaman**: Multi-Page Application (MPA) dengan entry point halaman terpisah
- **HTTP Client**: Axios dengan Global Request/Response Interceptor
- **State Management**: LocalStorage / Pinia
- **Design Tokens & Styling**: CSS Variables (`--color-*`), tipografi Inter, utilitas CSS responsif murni tanpa hardcoded hex di komponen
- **Production Web Server**: Nginx 1.27-Alpine (Multi-stage Dockerfile)

---

## 2. Ketentuan Teknis & Panduan Kepatuhan

1. **Arsitektur MPA:**
   * Setiap halaman utama memiliki entry point sendiri yang dibuild oleh Vite.
   * Navigasi ant halaman menggunakan URL halaman, bukan Vue Router history mode.
2. **Kepatuhan Kontrak API:**
   * Endpoint, payload, response resource, status, dan aturan privasi mengikuti
     [PRD.md](../PRD.md).
   * Detail visual, responsif, aksesibilitas, dan error UX mengikuti
     [DesignGuidelineUIUX.md](../DesignGuidelineUIUX.md).
3. **Manajemen Dependensi:**
   * Gunakan `pnpm` dan lockfile `pnpm-lock.yaml`.
   * Jangan mencampur workflow package manager lain dengan pengembangan
     berbasis pnpm.

---

## 🎨 Design System & Token Warna (Sesuai `DesignGuidelineUIUX.md`)

Aplikasi menerapkan sistem desain bernuansa **Teal** sebagai warna utama (*Primary*) dan **Amber** sebagai aksen tunggal (*Call-To-Action / CTA*). Seluruh komponen membaca variabel CSS global:

| Token CSS | Mode Terang (Light) | Mode Gelap (Dark) | Peruntukan & Makna Visual |
| :--- | :--- | :--- | :--- |
| `--color-primary` | `#0F766E` | `#2DD4BF` | Logo, link aktif, chip filter aktif, ring fokus keyboard (2px) |
| `--color-accent` | `#F59E0B` | `#F59E0B` | Satu tombol aksi utama (CTA) per layar (e.g. "Laporkan Barang") |
| `--color-success` | `#15803D` | `#4ADE80` | Badge Ditemukan (`found`), status `approved` |
| `--color-warning` | `#B45309` | `#FBBF24` | Timer < 24 jam, status `pending` (Menunggu Review) |
| `--color-info` | `#0369A1` | `#38BDF8` | Status `resolve_requested` (Menunggu Konfirmasi) |
| `--color-danger` | `#B91C1C` | `#F87171` | Badge Hilang (`lost`), timer < 12 jam, status `rejected`, pesan error |
| `--color-bg` | `#F8FAFC` | `#0B1220` | Latar belakang halaman |
| `--color-card` | `#FFFFFF` | `#121A2B` | Kartu konten, navbar, input form, modal dialog |
| `--color-text` | `#0F172A` | `#E8EEF7` | Teks utama |
| `--color-muted` | `#64748B` | `#94A3B8` | Teks sekunder, timer kedaluwarsa, status `resolved` |

---

## 📌 Checklist Implementasi Frontend (Terkait Soal UTS)

### ⏳ Task Terkait Soal 2: Frontend Implementation (10 Poin)
- [ ] **Setup Proyek & Lingkungan:**
  - Inisialisasi scaffold Vue 3 + Vite di direktori `/frontend`.
  - Konfigurasi variabel lingkungan `.env` (`VITE_API_URL`, `VITE_STORAGE_URL`).
  - Helper utilitas `buildImageUrl(path)` untuk resolver URL gambar aset storage.
  - Implementasi CSS variables theme loader (`light` & `dark`) tanpa kedipan (*flash of unstyled theme*).
- [ ] **Konfigurasi Axios Instance & Interceptors (`services/api.js`):**
  - Menyematkan header `Authorization: Bearer <token>` dan `Accept: application/json` secara otomatis.
  - Penanganan error HTTP semantik sesuai PRD & Guideline:
    - `401 Unauthorized`: Bersihkan session `localStorage` dan alihkan ke `/login`.
    - `403 Forbidden`: Tampilkan pesan akses ditolak (bukan hak akses role user/admin).
    - `404 Not Found`: Tampilkan antarmuka data tidak ditemukan.
    - `409 Conflict`: Tampilkan pesan konflik status dari respons backend (bukan error form field).
    - `422 Unprocessable Entity`: Petakan array `errors[field]` ke field formulir terkait.
    - `429 Too Many Requests`: Tampilkan notifikasi pembatasan laju (*rate limiting*).
    - `5xx / Network Error`: Tampilkan banner ramah pengguna (*"Layanan Sedang Dalam Pemeliharaan / Belum Tersedia"*) beserta tombol *Retry*.
- [ ] **Sistem Autentikasi (`/login` & `/register`):**
  - Form Login & Register terintegrasi API (`POST /api/login` & `POST /api/register`).
  - Simpan token di `localStorage` dan sinkronkan state profil via `GET /api/user`.
  - Validasi konfirmasi password dan domain email `@gmail.com`, sesuai
    validasi backend.
  - 📸 **Task Dokumentasi SS-04**: Form login terisi dan DevTools LocalStorage memperlihatkan token tersimpan.
- [ ] **Dashboard Publik (`/` - Mode Tamu/Guest):**
  - Grid kartu barang yang berstatus `approved` dari `GET /api/public/items` (responsif 1 kolom mobile, 2 kolom tablet, 3 kolom desktop).
  - Privasi kontak terlindungi: field kontak tidak ditampilkan untuk pengunjung publik.
  - Komponen `CountdownTimer.vue`: Indikator sisa waktu pasif 72 jam secara *real-time* berbasis `remaining_seconds` (Netral $\ge$ 24j, Kuning < 24j, Merah < 12j, Abu-abu jika habis).
  - Chip filter kategori & status tipe (Hilang / Ditemukan).
  - 📸 **Task Dokumentasi SS-05**: Dashboard publik mode tamu menampilkan grid kartu barang, gambar, badge tipe, dan timer aktif.
- [ ] **Form Pelaporan Barang (`/report` - User Authenticated):**
  - Formulir input field yang didukung backend: `item_name`, `category`, `location`, `current_storage`, `contact_info`, `reporter_contact_info`, `chronology`, dan `image`.
  - Dukungan upload foto barang (`multipart/form-data`) dengan preview gambar sebelum submit.
  - Status barang baru otomatis `pending` (menunggu moderasi admin).
  - 📸 **Task Dokumentasi SS-06**: Form pelaporan terisi lengkap beserta file foto dan alert konfirmasi sukses.
- [ ] **Dashboard Admin & Moderasi (`/admin`):**
  - Proteksi rute berbasis role (khusus akun dengan `role === 'admin'`).
  - **Tab 1 — Pengajuan Pending:** Moderasi pengajuan barang masuk (Tombol Setujui / Tolak).
  - **Tab 2 — Laporan Aktif Publik:** Monitoring barang tayang dan opsi hapus darurat.
  - **Tab 3 — Pesan Resolusi:** Memproses pengajuan laporan pihak ketiga dari `GET /api/item-reports` (Terima Laporan $\rightarrow$ kirim `accepted`; backend memicu `resolve_requested`, atau Tolak Laporan).
  - 📸 **Task Dokumentasi SS-07**: Dashboard admin dengan 3 tab inbox dan aksi moderasi.
- [ ] **Alur Resolusi & Repost (Sisi Pemilik):**
  - Halaman / dialog detail barang untuk konfirmasi penerimaan resolusi (`PATCH /api/items/{id}/status`).
  - Fitur Repost (`POST /api/items/{id}/repost`) untuk barang kedaluwarsa/selesai dengan proteksi cooldown 72 jam.

### ⏳ Task Terkait Soal 5: Multi-Stage Dockerfile (20 Poin)
- [ ] Pembuatan `frontend/Dockerfile` dengan strategi multi-stage:
  - Stage 1: Build aset statis menggunakan image `node:20-alpine`.
  - Stage 2: Sajikan aset statis menggunakan image ringan `nginx:1.27-alpine`.
- [ ] Konfigurasi `nginx.conf`:
  - Konfigurasi pemetaan entry point MPA dan fallback aset sesuai struktur halaman yang dibuild Vite.
  - Reverse proxy path `/api/` ke service Docker backend (`http://backend:80/api/`).
  - Reverse proxy path `/storage/` ke service Docker backend (`http://backend:80/storage/`).
- [ ] Pembuatan berkas `frontend/.dockerignore`.

---

## 📁 Rencana Struktur Direktori Layanan Frontend

```text
frontend/
├── public/                      # Aset publik statis (favicon, logo, dll)
├── src/
│   ├── assets/
│   │   ├── css/
│   │   │   └── main.css         # Token CSS variables (Light/Dark mode) & styling dasar
│   ├── components/              # Komponen modular reusable
│   │   ├── CardItem.vue         # Kartu informasi barang (foto, badge, lokasi, timer)
│   │   ├── StatusBadge.vue      # Badge status (Pending, Approved, Resolved, dll)
│   │   ├── CountdownTimer.vue   # Indikator hitung mundur 72 jam real-time
│   │   ├── BaseModal.vue        # Modal dialog reusable (konfirmasi hapus, preview, klaim)
│   │   ├── ToastNotification.vue# Notifikasi toast global
│   │   └── Navbar.vue           # Navigasi utama dengan deteksi auth & toggle dark mode
│   ├── pages/                   # Entry point halaman MPA
│   │   └── index.ts
│   ├── services/                # Konfigurasi Axios & API helper
│   │   └── api.js               # Global interceptor & URL builder
│   ├── stores/                  # Pinia store untuk state autentikasi & tema
│   │   ├── auth.js
│   │   └── theme.js
│   ├── views/                   # Halaman antarmuka utama
│   │   ├── PublicDashboard.vue  # Beranda browsing publik tamu
│   │   ├── LoginView.vue        # Halaman autentikasi login
│   │   ├── RegisterView.vue     # Halaman registrasi akun baru
│   │   ├── ReportItemView.vue   # Form submit laporan barang hilang/temuan
│   │   ├── ItemDetailView.vue   # Halaman detail barang & alur resolusi
│   │   └── AdminDashboard.vue   # Dashboard manajemen moderasi 3 tab admin
│   ├── App.vue                  # Root component
│   └── main.ts                  # Entry point fondasi aplikasi Vue 3
├── nginx.conf                   # Konfigurasi Nginx untuk Docker production
├── Dockerfile                   # Multi-stage Dockerfile
├── .dockerignore                # Pengecualian Docker build
├── .env.example                 # Template variabel lingkungan frontend
├── package.json                 # Dependensi dan scripts pnpm
├── pnpm-lock.yaml               # Lockfile dependensi pnpm
├── vite.config.ts               # Konfigurasi Vite bundler
└── README.md                    # Dokumentasi frontend
```

---

## ⚡ 3. Panduan Menjalankan Frontend Secara Lokal (Dev Mode)

```bash
# 1. Pindah ke direktori frontend
cd frontend

# 2. Pasang dependensi sesuai lockfile
pnpm install

# 3. Salin file template lingkungan
cp .env.example .env

# 4. Jalankan development server
pnpm dev
```

Aplikasi default dapat diakses pada `http://localhost:5173`.

### Perintah validasi

```bash
# Build production
pnpm build

# Preview hasil build production
pnpm preview
```
