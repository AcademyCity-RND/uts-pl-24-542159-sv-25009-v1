# 🎨 Frontend SPA Service — JejakBarang (Vue.js 3 & Vite)

Aplikasi antarmuka pengguna berbasis *Single Page Application* (SPA) untuk sistem **JejakBarang** (Pusat Informasi *Lost & Found* Kampus) yang dibangun menggunakan **Vue.js 3 (Composition API `<script setup>`)** dan bundler modern **Vite**. Aplikasi ini terintegrasi secara asinkron dengan RESTful API Backend melalui **Axios** dengan arsitektur komponen modular dan penanganan galat jaringan terpusat.

---

## 🛠️ Spesifikasi Tech Stack
- **Framework**: Vue.js 3 (`<script setup>` SFC)
- **Tooling / Bundler**: Vite
- **Routing**: Vue Router 4 (HTML5 History Mode & Navigation Guards)
- **HTTP Client**: Axios dengan Global Request/Response Interceptor
- **State Management**: LocalStorage / Pinia
- **Production Web Server**: Nginx 1.27-Alpine

---

## 📌 Checklist Implementasi Frontend (Terkait Soal UTS)

### ⏳ Task Terkait Soal 2: Frontend SPA (10 Poin)
- [ ] Inisialisasi project Vue 3 + Vite di direktori `/frontend`.
- [ ] Konfigurasi Axios instance dengan Global Interceptor:
  - Otomatis menyematkan header `Authorization: Bearer <BEARER_TOKEN>` untuk request terproteksi.
  - Penanganan error 401 (otomatis redirect ke `/login` jika sesi habis).
  - Penanganan error jaringan / 503 (menampilkan tampilan ramah pengguna *"Layanan Sedang Dalam Pemeliharaan"* alih-alih layar putih kosong).
- [ ] Halaman Login & Registrasi:
  - Form input kredensial (Email & Password).
  - Menyimpan token autentikasi dan role pengguna di `localStorage`.
  - Otomatis melakukan *redirection* ke dashboard setelah sukses.
- [ ] Dashboard Publik (Guest View - Tanpa Login):
  - Card Grid menampilkan barang hilang/ditemukan yang berstatus `approved` dari `GET /api/public/items`.
  - Menampilkan foto barang, badge kategori, lokasi, dan info penyimpanan yang aman untuk publik (field kontak dihilangkan demi privasi pelapor).
  - Indikator hitung mundur (*Countdown Timer*) sisa masa aktif 72 jam secara real-time.
- [ ] Halaman Pelaporan Barang (User Authenticated):
  - Form input data barang (Nama, Kategori, Lokasi, Penyimpanan, Kontak, Kronologi).
  - Dukungan upload file gambar (`<input type="file">`) menggunakan `FormData`.
  - Status barang baru otomatis `pending` (menunggu persetujuan admin).
- [ ] Dashboard Manajemen & Inbox Admin:
  - **Tab 1 — Pengajuan Pending:** Meninjau dan menyetujui (ACC) atau menolak laporan baru.
  - **Tab 2 — Laporan Aktif Publik:** Memantau barang yang sedang tayang, opsi hapus paksa jika tidak sesuai.
  - **Tab 3 — Pesan Resolusi:** Memproses permintaan penyelesaian dari penemu atau pelapor.
- [ ] Alur Resolusi Barang (Resolution Flow):
  - Pelapor dapat menandai selesai (`resolved`) langsung pada barang miliknya.
  - Pengguna lain dapat menggunakan fitur *"Lapor Admin"* untuk memicu permintaan resolusi.
  - Fitur *"Repost"* untuk memperpanjang masa tayang barang yang kedaluwarsa.
- [ ] Pengaturan URL API dinamis via file lingkungan `.env` (`VITE_API_URL`), tidak ada IP/URL yang di-*hardcode*.

### ⏳ Task Terkait Soal 5: Multi-Stage Dockerfile (20 Poin)
- [ ] Pembuatan `frontend/Dockerfile` dengan strategi multi-stage:
  - Stage 1: Build aset statis menggunakan image `node:20-alpine`.
  - Stage 2: Sajikan aset statis menggunakan image ringan `nginx:1.27-alpine`.
- [ ] Memastikan image akhir tidak memuat folder `node_modules` (ramping dan aman).
- [ ] Konfigurasi `nginx.conf`:
  - Direktif `try_files $uri $uri/ /index.html;` (pencegahan error 404 saat *refresh* rute SPA).
  - Reverse proxy path `/api/` ke service Docker `http://backend:80/api/`.
  - Reverse proxy path `/storage/` ke service Docker `http://backend:80/storage/` (menampilkan foto barang).
- [ ] Pembuatan berkas `.dockerignore` di folder `frontend/`.

---

## 📁 Struktur Direktori Layanan Frontend

```text
frontend/
├── public/                      # Aset publik statis
├── src/
│   ├── assets/                  # Styling CSS / Icon grafis
│   ├── components/              # Komponen modular reusable
│   │   ├── CardItem.vue         # Kartu informasi barang hilang/ditemukan
│   │   ├── StatusBadge.vue      # Badge status (Pending, Approved, Resolved, dll)
│   │   ├── CountdownTimer.vue   # Indikator sisa waktu pasif 3 hari
│   │   ├── BaseModal.vue        # Modal dialog konfirmasi & form popup
│   │   └── Navbar.vue           # Navigasi utama dengan status auth
│   ├── router/                  # Konfigurasi Vue Router & route guards
│   ├── services/                # Axios instance & error interceptor
│   ├── views/                   # Halaman antarmuka
│   │   ├── PublicDashboard.vue  # Tampilan browsing tamu (tanpa login)
│   │   ├── LoginView.vue        # Halaman autentikasi login
│   │   ├── RegisterView.vue     # Halaman registrasi user baru
│   │   ├── ReportItemView.vue   # Form lapor barang baru (upload foto)
│   │   └── AdminDashboard.vue   # Dashboard approval 3-tab inbox admin
│   ├── App.vue                  # Root component
│   └── main.js                  # Entry point aplikasi
├── nginx.conf                   # Konfigurasi web server Nginx (SPA + reverse proxy)
├── Dockerfile                   # Multi-stage Docker build
├── .dockerignore                # Pengecualian build Docker
├── .env.example                 # Template variabel lingkungan frontend
├── package.json                 # Dependensi Node.js & skrip build
├── vite.config.js               # Konfigurasi bundler Vite
└── README.md                    # Dokumentasi frontend
```

---

## ⚡ Panduan Menjalankan Frontend Secara Lokal (Dev Mode)
```bash
# 1. Pindah ke direktori frontend
cd frontend

# 2. Pasang dependensi
npm install

# 3. Buat file .env lokal
cp .env.example .env

# 4. Jalankan development server
npm run dev
```
Aplikasi akan aktif di `http://localhost:5173`.
