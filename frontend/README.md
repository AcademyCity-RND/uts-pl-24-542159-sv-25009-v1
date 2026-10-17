# 🎨 Frontend SPA Service — Vue.js 3 & Vite

Aplikasi antarmuka pengguna berbasis *Single Page Application* (SPA) yang dibangun menggunakan **Vue.js 3 (Composition API)** dan bundler modern **Vite**. Aplikasi ini terintegrasi dengan RESTful API Backend secara asinkron menggunakan **Axios**.

---

## 🛠️ Spesifikasi Tech Stack
- **Framework**: Vue.js 3 (`<script setup>` SFC)
- **Tooling / Bundler**: Vite
- **Routing**: Vue Router 4 (HTML5 History Mode)
- **HTTP Client**: Axios dengan Global Interceptor
- **State Management**: LocalStorage / Pinia
- **Production Web Server**: Nginx 1.27-Alpine

---

## 📌 Checklist Implementasi Frontend (Terkait Soal UTS)

### ⏳ Task Terkait Soal 2: Frontend SPA (10 Poin)
- [ ] Inisialisasi project Vue 3 + Vite di direktori `/frontend`.
- [ ] Konfigurasi Axios instance dengan Global Request Interceptor (otomatis melampirkan `Authorization: Bearer <token>`).
- [ ] Konfigurasi Axios Response Interceptor (menangani error 401 dan redirect ke login).
- [ ] Halaman Login: input kredensial, menyimpan token di `localStorage`, dan redirect ke dashboard.
- [ ] Halaman CRUD Resource:
  - [ ] Tabel/Daftar Data (Read).
  - [ ] Form Tambah Data (Create) dengan penanganan pesan error validasi (422).
  - [ ] Form Ubah Data (Update) dengan pengisian awal form (*pre-filled*).
  - [ ] Tombol & Modal Konfirmasi Hapus Data (Delete).
- [ ] Pengaturan URL API dinamis via file lingkungan `.env` (`VITE_API_URL`), tidak ada *hardcoded* IP/URL.

### ⏳ Task Terkait Soal 5: Multi-Stage Dockerfile (20 Poin)
- [ ] Pembuatan `frontend/Dockerfile` dengan strategi multi-stage:
  - Stage 1: Build aset statis menggunakan image `node:20-alpine`.
  - Stage 2: Sajikan aset statis menggunakan image ringan `nginx:1.27-alpine`.
- [ ] Memastikan image akhir tidak memuat folder `node_modules`.
- [ ] Konfigurasi `nginx.conf` dengan direktif `try_files $uri $uri/ /index.html;` (pencegahan error 404 pada SPA).
- [ ] Konfigurasi reverse proxy Nginx untuk meneruskan `/api/` ke service Docker `backend`.
- [ ] Pembuatan berkas `.dockerignore` di folder `frontend/`.

---

## 📁 Struktur Direktori Layanan Frontend

```text
frontend/
├── public/                      # Aset statis publik
├── src/
│   ├── assets/                  # CSS styling / aset grafis
│   ├── components/              # Komponen modular reusable (Navbar, Modal)
│   ├── router/                  # Konfigurasi Vue Router & Navigation Guards
│   ├── services/                # Axios instance & API interceptor
│   ├── views/                   # Tampilan utama (LoginView, CrudView)
│   ├── App.vue                  # Root component
│   └── main.js                  # Entry point aplikasi
├── nginx.conf                   # Konfigurasi web server Nginx produksi
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
