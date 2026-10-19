# 📋 Product Requirements Document (PRD) & Technical Architecture
# JejakBarang — Pusat Lost & Found Anti-Tenggelam

> **Repositori Pegangan Teknis:** UTS Konstruksi dan Evolusi Perangkat Lunak (KEPL)  
> **Arsitektur:** Decoupled Monorepo (Laravel 11 FrankenPHP REST API & Vue.js 3 SPA)  
> **Infrastruktur:** Docker Compose Orchestration & CI/CD GitHub Actions ke GHCR

---

## 1. Deskripsi Singkat & Latar Belakang

**JejakBarang** adalah aplikasi web *client-server* modern yang berfungsi sebagai papan pengumuman interaktif terpusat untuk barang hilang dan ditemukan (*Lost & Found*) di lingkungan kampus. Masalah utama yang diselesaikan adalah fenomena pengumuman barang hilang di grup obrolan (WhatsApp/Telegram) dan media sosial yang sangat cepat **tenggelam** tertimbun pesan lain, tidak terverifikasi, dan sulit dilacak status akhirnya (apakah sudah kembali ke pemiliknya atau belum).

Sistem ini menerapkan alur kendali **"User Propose $\rightarrow$ Admin Approve"** untuk menjamin keabsahan informasi sebelum tayang di ranah publik. Aplikasi dibangun dengan pemisahan peran yang tegas (*Decoupled Architecture*): Backend Laravel 11 berperforma tinggi di atas server aplikasi **FrankenPHP**, Frontend SPA interaktif berbasis **Vue.js 3**, penyimpanan relasional **MySQL** yang persisten, serta kontainerisasi penuh menggunakan **Docker Compose**.

### Glosarium Terminologi:
* **Item / Laporan Barang (`lost_items`):** Entitas postingan pengumuman barang hilang atau barang temuan yang diajukan oleh pengguna pemilik/penemu awal.
* **Report / Laporan Pihak Ketiga (`item_reports`):** Klaim atau laporan informasi penemuan yang diajukan oleh pengguna lain terhadap postingan barang yang sedang tayang aktif.

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

#### 1. Endpoint Autentikasi & Registrasi:
* `POST /api/register`: Mendaftarkan pengguna baru dengan kontrak API:
  * **Payload Request:**
    ```json
    {
      "name": "Mahasiswa Contoh",
      "email": "mahasiswa@example.com",
      "password": "password123",
      "password_confirmation": "password123",
      "phone_number": "08123456789"
    }
    ```
  * **Aturan Validasi:** `name` (required, string, max:255), `email` (required, email, unique:users), `password` (required, min:8, confirmed), `phone_number` (nullable, string, max:20).
  * **Rate Limiting:** Maksimal 5 permintaan per menit per alamat IP (`throttle:5,1`).
  * **Proteksi Keamanan:** Kolom `role` **wajib di-hardcode menjadi `user`** di dalam Controller — tidak boleh diterima dari body request untuk mencegah *privilege escalation*.
  * **Respons Sukses (`201 Created`):**
    ```json
    {
      "message": "Pendaftaran berhasil.",
      "data": {
        "user": {
          "id": 1,
          "name": "Mahasiswa Contoh",
          "email": "mahasiswa@example.com",
          "role": "user"
        },
        "token": "<BEARER_TOKEN>"
      }
    }
    ```
* `POST /api/login`: Memvalidasi kredensial (`email` & `password`) dan menerbitkan Bearer Token Sanctum (`throttle:5,1`).
  * **Respons Sukses (`200 OK`):**
    ```json
    {
      "message": "Autentikasi berhasil.",
      "data": {
        "user": {
          "id": 1,
          "name": "Mahasiswa Contoh",
          "email": "mahasiswa@example.com",
          "role": "user"
        },
        "token": "<BEARER_TOKEN>"
      }
    }
    ```
* `POST /api/logout`: Dilindungi `auth:sanctum`, merevokasi dan menghapus token aktif pengguna saat ini.

#### 2. Rute Akses Publik vs Terproteksi:
* **Rute Publik (Guest Browsing):**
  * `GET /api/public/items`: Menampilkan daftar barang hilang/ditemukan yang berstatus `approved` dan masa aktifnya belum kedaluwarsa (< 72 jam). Endpoint ini dapat diakses bebas tanpa token oleh peramban tamu.
  * **Privasi Kontak:** Seluruh atribut kontak (`reporter_contact_info` dan `contact_info`) **dihilangkan sama sekali (omitted)** dari payload JSON respons publik menggunakan fitur conditional attribute `when()` pada Laravel API Resource.
* **Rute Terproteksi (Wajib Middleware `auth:sanctum`):**
  * Seluruh endpoint CRUD dan pengelolaan wajib dilindungi Sanctum:
    * `GET /api/items`: Mengambil daftar barang untuk kebutuhan dashboard authenticated.
      * Mendukung query parameter: `?status=pending|approved|rejected|resolve_requested|resolved&page=1&per_page=10`.
      * **Nilai Default:** `page=1`, `per_page=10` (maksimal 50). Jika parameter `status` tidak disertakan: Admin melihat seluruh item pada semua status, sedangkan User biasa melihat seluruh item miliknya sendiri.
      * **Privasi Kontak Bertingkat:** Admin dapat melihat seluruh field kontak. User biasa hanya dapat melihat field kontak pada **barang miliknya sendiri**, sedangkan pada barang milik orang lain kunci field kontak **dihilangkan (omitted)** dari JSON.
    * `GET /api/items/{id}`: Menampilkan detail 1 barang secara lengkap. Kunci field kontak hanya disertakan kepada **pemilik barang** atau **admin**.
    * `POST /api/items`: Membuat laporan barang baru (otomatis berstatus `pending`, menyertakan file gambar via `multipart/form-data`).
    * `POST /api/items/{id}` *(dengan `_method: PUT`)*: Memperbarui data laporan barang (mendukung update file gambar). Hanya dapat dilakukan pada barang berstatus `pending` atau `rejected`. Jika dilakukan pada barang berstatus `rejected`, sistem **secara otomatis mereset status menjadi `pending`** dan mengosongkan `approved_at = null` untuk peninjauan ulang admin.
    * `DELETE /api/items/{id}`: Menghapus data laporan barang (hanya pemilik atau admin). Mengembalikan `200 OK` dengan pesan konfirmasi.
    * `PATCH /api/items/{id}/status`: Memperbarui status laporan sesuai State Transition Matrix.
      * **Payload Request:** `{ "status": "approved" }` (Validasi: `in:approved,rejected,resolved`).
      * ⚠️ **Pengecualian Status:** Nilai `resolve_requested` **DILARANG** dikirim ke endpoint ini. Status tersebut hanya dapat dipicu secara internal oleh sistem ketika Admin menerima laporan penemuan pihak ketiga.
      * ⚠️ **Validasi State Asal:** Status `rejected` hanya sah diterapkan jika status barang saat ini adalah `pending`. Mengubah barang `approved` atau `resolved` menjadi `rejected` wajib ditolak dengan `409 Conflict`.
    * `POST /api/items/{id}/report`: Fitur pelaporan bagi pihak ketiga (non-owner).
      * **Payload Request:**
        ```json
        {
          "message": "Barang terlihat disimpan di Pos Satpam Gedung Utama lantai 1."
        }
        ```
      * **Validasi & Otorisasi:** `message` (required, string, min:10, max:2000). Pemilik barang **dilarang melaporkan barangnya sendiri** (ditolak `403 Forbidden`).
      * **Pencegahan Spam (Anti-Duplicate):** Seorang pengguna hanya boleh memiliki maksimal 1 laporan berstatus `pending` untuk barang yang sama. Jika mencoba mengirim lagi, sistem mengembalikan respons **`409 Conflict`** (*"Anda sudah memiliki laporan aktif yang sedang ditinjau untuk barang ini"*).
      * **Ketentuan State:** Hanya dapat dilakukan jika barang berstatus `approved`, belum kedaluwarsa (`approved_at > now()->subHours(72)`), dan belum memiliki laporan lain yang berstatus `accepted` / `resolve_requested`. Jika melanggar, kembalikan respons **`409 Conflict`**.
      * **Rate Limiting:** Maksimal 10 permintaan per menit per pengguna terautentikasi (`throttle:10,1`).
    * `POST /api/items/{id}/repost`: Memperpanjang masa tayang barang yang sudah kedaluwarsa (> 72 jam) atau berstatus `resolved`. Mengubah status menjadi `approved` serta memperbarui `approved_at = now()` dan `resolved_at = null`. Dibatasi maksimal 1 kali per 72 jam (jika dilanggar, kembalikan `409 Conflict`).
    * `GET /api/item-reports`: Endpoint **khusus Admin** (User biasa ditolak dengan `403 Forbidden`). Digunakan untuk meninjau seluruh laporan pihak ketiga. Mendukung query filter: `?status=pending|accepted|rejected|closed&page=1&per_page=10`.
    * `PATCH /api/item-reports/{id}/status`: Endpoint **khusus Admin** untuk memproses laporan pihak ketiga:
      * **Payload Request:** `{ "status": "accepted" }` (Validasi ketat: `in:accepted,rejected`). Status `closed` **dilarang diset secara manual** oleh Admin (ditolak `403 Forbidden`).
      * **Dual Pessimistic Locking & Database Transaction:** Aksi penerimaan laporan wajib dieksekusi di dalam **Database Transaction (`DB::transaction`)** dengan mengunci baris data laporan sekaligus baris data barang terkait:
        ```php
        DB::transaction(function () use ($reportId) {
            $report = ItemReport::lockForUpdate()->findOrFail($reportId);
            $item = LostItem::lockForUpdate()->findOrFail($report->lost_item_id);

            // Verifikasi integritas di dalam lock
            if ($report->status !== 'pending') {
                abort(409, 'Laporan sudah pernah diproses sebelumnya.');
            }
            if ($item->status !== 'approved' || $item->approved_at <= now()->subHours(72)) {
                abort(409, 'Barang tidak dalam status aktif atau telah kedaluwarsa.');
            }
            if ($item->itemReports()->where('status', 'accepted')->exists()) {
                abort(409, 'Barang sudah memiliki proses resolusi aktif.');
            }

            $report->update(['status' => 'accepted']);
            $item->update(['status' => 'resolve_requested']);
        });
        ```
      * Aksi `accepted`: Menandai laporan diterima dan secara otomatis mengubah status item terkait di `lost_items` menjadi `resolve_requested`.
      * Aksi `rejected`: Menolak laporan pihak ketiga tanpa mengubah status item utama.
    * **Mekanisme Notifikasi Resolusi (In-App):** Notifikasi permintaan konfirmasi resolusi kepada pemilik barang bersifat **In-App** (direpresentasikan secara otomatis melalui hasil `GET /api/items` milik owner yang menampilkan badge khusus saat berstatus `resolve_requested`), tidak memerlukan layanan eksternal WebSocket atau email.

#### 3. Penanganan Unggahan Gambar & Method Spoofing (`PUT`):
* **Limitasi Native PHP Stream:** PHP secara bawaan tidak dapat membaca payload `$_FILES` pada HTTP method `PUT` murni dengan `multipart/form-data`.
* **Solusi Arsitektural:** Pada proses pembaruan data (Update) yang melibatkan foto baru, Frontend mengirimkan request via HTTP Method **`POST`** dengan menyisipkan field spoofing **`_method: 'PUT'`** ke dalam objek `FormData`. Laravel akan secara otomatis membaca dan mengeksekusi request tersebut melalui rute `Route::put('/api/items/{id}', ...)`.
* Berkas gambar divalidasi (`mimes:jpg,jpeg,png|max:2048`) dan disimpan di direktori `storage/app/public/lost_items/`.

#### 4. Otorisasi & Keamanan:
* **Perlindungan Mass Assignment:** Kolom sensitif seperti `status`, `approved_at`, `resolved_at`, dan `role` wajib dilindungi dari Mass Assignment (dikeluarkan dari array `$fillable` atau didefinisikan dalam `$guarded`).
* **Laravel Policy & Gate:** Memastikan bahwa aksi modifikasi atau resolusi barang oleh pihak yang tidak berhak akan ditolak dengan mengembalikan respons HTTP **`403 Forbidden`**.

#### 5. Format Standar Respons JSON & HTTP Status Code:
* **Format Respons Sukses:**
  ```json
  {
    "message": "Operasi berhasil diselesaikan.",
    "data": { ... }
  }
  ```
* **Format Respons Error:**
  ```json
  {
    "message": "Pesan error yang deskriptif.",
    "errors": { "field": ["Detail validasi jika ada"] }
  }
  ```
* **Daftar HTTP Status Code yang Digunakan:**
  * `200 OK`: Permintaan berhasil diselesaikan.
  * `201 Created`: Entitas baru (user atau laporan barang) berhasil dibuat.
  * `401 Unauthorized`: Token tidak valid, kedaluwarsa, atau kredensial salah.
  * `403 Forbidden`: Pengguna tidak memiliki hak otorisasi untuk resource target.
  * `404 Not Found`: Resource yang diminta tidak ditemukan di database.
  * `409 Conflict`: Terjadi konflik state (misal: repost saat barang belum kedaluwarsa, lapor barang yang sudah dalam status `resolve_requested`, duplikat laporan pending, atau transisi state yang dilarang).
  * `422 Unprocessable Entity`: Validasi input data pada `FormRequest` gagal.
  * `429 Too Many Requests`: Pembatasan laju request (*Rate Limiting*) terlampaui.

#### 6. Paginasi:
* Endpoint daftar (`GET /api/items`, `GET /api/public/items`, `GET /api/item-reports`) menggunakan paginasi Laravel standar. Struktur respons JSON:
  ```json
  {
    "data": [...],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "last_page": 5, "per_page": 10, "total": 48 }
  }
  ```

---

### B. Database Schema & Indexing

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
* `user_id` (ForeignKey $\rightarrow$ `users.id`, `cascadeOnDelete()`)
* `item_name` (String: nama barang hilang/ditemukan)
* `category` (Enum: `lost`, `found`)
* `location` (String: tempat terakhir terlihat atau ditemukan)
* `current_storage` (String: posisi barang saat ini)
* `contact_info` (String: kontak penanggung jawab barang saat ini — **akses terbatas**)
* `reporter_contact_info` (String: nomor kontak pelapor/pembuat postingan — **akses terbatas**)
* `chronology` (Text: deskripsi kronologi kejadian)
* `status` (Enum: `pending`, `approved`, `rejected`, `resolve_requested`, `resolved` — default: `pending`)
* `image_path` (String, Nullable: path relatif file gambar yang diunggah)
* `approved_at` (Timestamp, Nullable: waktu disetujui oleh admin)
* `resolved_at` (Timestamp, Nullable: waktu barang berhasil diselesaikan/dikembalikan)
* `created_at` & `updated_at` (Timestamp)
* **Index Performa:**
  * `$table->index('status');`
  * `$table->index('approved_at');`
  * `$table->index(['status', 'approved_at']);` *(Composite index untuk optimasi kueri papan publik)*

> **Computed Fields (tidak disimpan di DB, dihitung di backend saat respons):**
> * `expires_at`: `approved_at + 72 jam` (format ISO 8601)
> * `is_expired`: `boolean` — bernilai `true` jika `now() > approved_at + 72 jam`
> * `remaining_seconds`: `integer` — sisa detik hingga kedaluwarsa (0 jika sudah expired)

#### Tabel `item_reports`:
* `id` (BigIncrements, Primary Key)
* `lost_item_id` (ForeignKey $\rightarrow$ `lost_items.id`, `cascadeOnDelete()`)
* `reporter_user_id` (ForeignKey $\rightarrow$ `users.id`, `restrictOnDelete()`)
* `message` (Text: isi laporan dari pelapor ketiga)
* `status` (Enum: `pending`, `accepted`, `rejected`, `closed` — default: `pending`)
* `created_at` & `updated_at` (Timestamp)
* **Index Performa:**
  * `$table->index(['lost_item_id', 'status']);` *(Composite index untuk pencarian cepat laporan per barang)*
  * `$table->index(['reporter_user_id', 'lost_item_id', 'status']);` *(Untuk verifikasi anti-duplicate check)*

> **Definisi Status Laporan (`item_reports`):**
> * `pending`: Menunggu peninjauan oleh Admin.
> * `accepted`: Disetujui oleh Admin dan sedang memicu proses resolusi (`resolve_requested`) pada barang terkait.
> * `rejected`: Ditolak oleh Admin (tidak terbukti / tidak valid).
> * `closed`: Laporan tidak lagi aktif karena proses resolusi telah selesai diterima pemilik atau dibatalkan karena ditolak pemilik.

> **Aturan Integritas Multiple Reports:**
> 1. Satu barang dapat memiliki banyak laporan (`hasMany`), namun hanya **1 laporan berstatus `accepted`** yang dapat aktif memicu status `resolve_requested` pada satu waktu.
> 2. Ketika pemilik barang menerima resolusi (barang beralih menjadi `resolved`), laporan yang diterima ditandai `accepted`, dan seluruh laporan lain yang masih berstatus `pending` untuk barang tersebut otomatis diubah oleh sistem menjadi **`closed`**.
> 3. Ketika pemilik barang menolak resolusi (barang kembali ke `approved`), laporan yang sebelumnya `accepted` otomatis diubah oleh sistem menjadi **`closed`**.

#### Data Seed (Akun Demo — Khusus Lingkungan Dev & Test):
> ⚠️ **Catatan Keamanan:** Kredensial berikut hanya berlaku pada tahap *local development* dan pengujian otomatis. Tidak boleh digunakan pada deployment publik.
* **Admin:** `admin@jejakbarang.test` / `password123` (role: `admin`)
* **User:** `mahasiswa@jejakbarang.test` / `password123` (role: `user`)

---

### C. State Transition Matrix & Manajemen Timestamp

Tabel berikut mendefinisikan transisi status yang **diizinkan** secara ketat beserta siklus waktu (`approved_at` dan `resolved_at`). Setiap percobaan transisi di luar matriks ini wajib ditolak oleh backend dengan respons `403 Forbidden` atau `409 Conflict`.

| Status Asal | Status Tujuan | Aktor | Aksi / Endpoint | Dampak `approved_at` | Dampak `resolved_at` |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `pending` | `approved` | Admin | `PATCH /api/items/{id}/status` | Diisi `now()` | Tetap `null` |
| `pending` | `rejected` | Admin | `PATCH /api/items/{id}/status` | Tetap `null` | Tetap `null` |
| `rejected` | `pending` | Owner | `POST /api/items/{id}` (`_method: PUT`) | Direset `null` | Tetap `null` |
| `approved` | `resolve_requested` | Admin | `PATCH /api/item-reports/{id}/status` (set `accepted`) | Tetap | Tetap `null` |
| `approved` | `resolved` | Owner | `PATCH /api/items/{id}/status` | Tetap | Diisi `now()` |
| `resolve_requested` | `resolved` | Owner | `PATCH /api/items/{id}/status` | Tetap | Diisi `now()` |
| `resolve_requested` | `approved` | Owner | `PATCH /api/items/{id}/status` | **Tetap (Waktu Asli)** | Tetap `null` |
| `resolved` | `approved` | Owner | `POST /api/items/{id}/repost` | Direset `now()` | Direset `null` |
| `approved` *(expired)* | `approved` | Owner | `POST /api/items/{id}/repost` | Direset `now()` | Tetap `null` |

> 📌 **Catatan Khusus Penolakan Resolusi:** Pada transisi `resolve_requested -> approved` (pemilik menolak klaim penemu), nilai `approved_at` sengaja dipertahankan memakai waktu asli persetujuan awal (tidak diperpanjang otomatis). Jika waktu tayang 72 jam sudah habis saat proses klaim berlangsung, maka saat ditolak barang akan kembali berstatus `approved` namun langsung berstatus kedaluwarsa (`is_expired = true`). Pemilik dapat memanfaatkan fitur **Repost** jika ingin memperpanjang masa tayang.

---

### D. Alur Kerja Resolusi Barang (Resolution Flow)

```text
Kasus A: Diselesaikan oleh Pemilik Sendiri
[Pemilik Barang] ──(Klik Tombol "Selesaikan")──> [Status: 'resolved'] (resolved_at = now())

Kasus B: Ditemukan oleh Pihak Lain (Fitur Lapor Admin)
[Pihak Ketiga] ──(Lapor Admin: Barang Sudah Ditemukan)──> [Tabel item_reports: 'pending']
                                                                │
[Admin Meninjau Inbox item-reports]:                            │
   ├── [Tolak Laporan] ──> [item_reports: 'rejected']           │
   └── [Terima Laporan (Dual Lock)] ──> [item_reports: 'accepted'] ──┘
                                                │
                                                ▼
                              [Status lost_items: 'resolve_requested']
                                                │
                                                ▼ (Notifikasi Muncul di Dashboard Pelapor)
[Pelapor Memilih]:
   ├── "Terima" ──> [Status lost_items: 'resolved'] (resolved_at = now())
   │                └── Seluruh item_reports 'pending' lainnya otomatis menjadi 'closed'
   └── "Tolak"  ──> [Status lost_items: 'approved'] (Kembali tayang, approved_at tetap)
                    └── item_reports yang bersangkutan otomatis diubah sistem menjadi 'closed'
```

---

### E. Mekanisme Masa Aktif & Passive TTL 72 Jam

1. **Logika Bisnis Pasif (Tanpa Daemon Cronjob):**
   * Setiap postingan yang di-*approve* admin mencatat waktu persetujuan pada kolom `approved_at`.
   * Pada pemanggilan rute publik `GET /api/public/items`, database menyaring barang secara pasif yang benar-benar tayang di bawah 72 jam:
     ```php
     LostItem::where('status', 'approved')
         ->where('approved_at', '>', now()->subHours(72))
         ->latest('approved_at')
         ->paginate(10);
     ```
   * Postingan yang telah melewati 72 jam secara otomatis tidak dimunculkan di publik tanpa membebani sistem dengan penjadwal background (*cronless approach*).
2. **Countdown Timer UI:**
   * Frontend menghitung sisa waktu tayang aktif berdasarkan computed field `remaining_seconds` yang dikirim backend:
     $$\text{Sisa Waktu} = ({\text{approved\_at}} + 72\text{ jam}) - \text{Waktu Sekarang}$$
   * Ditampilkan pada komponen `CountdownTimer.vue` di setiap kartu barang.
3. **Fitur Repost:**
   * Pelapor dapat memperpanjang masa tayang barang yang telah kedaluwarsa (*expired*) atau yang berstatus `resolved` dengan menekan tombol **Repost**.
   * Repost memperbarui `approved_at = now()`, mengosongkan `resolved_at = null`, dan mereset status ke `approved`.
   * Batasan: **maksimal 1x repost per 72 jam** (backend memvalidasi bahwa `approved_at` barang saat ini sudah lebih dari 72 jam lalu; jika belum, kembalikan respons `409 Conflict`).

---

### F. Frontend SPA (Vue.js 3 & Vite)

#### 1. Arsitektur Komponen Modular (Reusable Components):
* `CardItem.vue`: Kartu representasi barang lengkap dengan foto, badge kategori, lokasi, dan countdown timer.
* `StatusBadge.vue`: Komponen penanda status visual (Pending: Kuning, Approved: Hijau, Resolve Requested: Biru, Resolved: Abu-abu, Rejected: Merah).
* `CountdownTimer.vue`: Indikator waktu sisa pasif 72 jam secara *real-time*.
* `BaseModal.vue`: Modal dialog reusable untuk form konfirmasi hapus, preview gambar, dan form pelaporan.
* `Navbar.vue`: Navigasi responsif yang mendeteksi state login user/admin.

#### 2. Struktur Antarmuka & Halaman:
* **Dashboard Publik (`/`):** Tampilan grid kartu barang yang berstatus `approved` tanpa memerlukan login. Field kontak **dihilangkan secara mutlak** di halaman publik.
* **Halaman Login & Register (`/login` & `/register`):** Formulir autentikasi pengguna. Token disimpan di `localStorage` dan status peran disimpan di store state.
* **Halaman Lapor Barang (`/report`):** Formulir submit barang hilang/ditemukan dengan upload gambar `FormData`.
* **Dashboard Admin (`/admin`):**
  * **Tab 1 — Pengajuan Pending:** Meninjau laporan barang masuk (tombol ACC / Tolak).
  * **Tab 2 — Laporan Aktif Publik:** Memantau barang yang sedang tayang, dengan opsi hapus paksa jika melanggar.
  * **Tab 3 — Pesan Resolusi:** Memproses pengajuan laporan pihak ketiga dari tabel `item_reports` (tombol Terima Laporan $\rightarrow$ memicu `resolve_requested`, atau Tolak Laporan).

#### 3. Penanganan Error Global (Error Handling UI/UX):
* Axios Interceptor mendeteksi kegagalan koneksi (*Network Error*, *503 Service Unavailable*, atau *Connection Refused*).
* Sistem menampilkan antarmuka ramah pengguna (*"Server Sedang Dalam Pemeliharaan / Layanan Belum Tersedia"*) alih-alih layar putih kosong (*blank screen*).

---

## 4. Infrastruktur & Orkestrasi Kontainer

### A. Jaringan Terpadu Docker Compose
* **Frontend Service (`frontend`):** Port host `80:80`. Berbasis Nginx 1.27 Alpine.
* **Backend Service (`backend`):** Port host `8000:80`. Berbasis FrankenPHP 1.4 PHP 8.3.
* **Database Service (`database`):** Port host `3306:3306`. Berbasis MySQL 8.0.

### B. MySQL Healthcheck & Urutan Startup
Untuk mencegah kondisi *race condition* di mana container backend mencoba terhubung ke MySQL sebelum database siap menerima koneksi, konfigurasi `docker-compose.yml` wajib menyertakan:
```yaml
services:
  database:
    image: mysql:8.0
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s

  backend:
    depends_on:
      database:
        condition: service_healthy
```

### C. Konfigurasi Nginx Reverse Proxy (`frontend/nginx.conf`)
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

### D. Persistensi Data (Named Volumes)
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
   * **Job 1 (Backend Test):** Menjalankan environment PHP 8.3 dengan **service container MySQL 8.0** aktif (`services: mysql:`), instalasi dependensi Composer, migrasi database uji, dan eksekusi otomatis 12 skenario `php artisan test` untuk memvalidasi *pessimistic row locking* di lingkungan CI.
   * **Job 2 (Frontend Build):** Menjalankan environment Node.js 20, instalasi `npm ci`, dan kompilasi `npm run build`.
2. **Continuous Deployment (CD) & Keamanan Secret:**
   * Dipicu **HANYA** saat terjadi push/merge ke branch `main`.
   * Membangun Docker Image Backend & Frontend. Berkas `.env`, `APP_KEY`, kredensial basis data, atau rahasia sensitif lainnya **DILARANG KERAS di-*bake* (disalin permanen) ke dalam layer Docker Image**. Rahasia hanya disuntikkan secara dinamis saat *runtime container* melalui environment variables / GitHub Secrets.
   * Mengunggah (*push*) image ke **GitHub Container Registry (`ghcr.io`)** menggunakan penandaan tag Commit SHA (`${{ github.sha }}`).

---

## 6. Matriks Pengujian Otomatis (*Feature Testing Matrix*)

Sistem wajib lolos 12 skenario pengujian otomatis (`php artisan test`) untuk menjamin kepatuhan seluruh aturan bisnis:

| No | Skenario Pengujian | Target Endpoint / Aksi | Ekspektasi Respons & State |
| :-: | :--- | :--- | :--- |
| 1 | **Privasi Guest** | `GET /api/public/items` | HTTP 200, kunci `contact_info` & `reporter_contact_info` **omitted** |
| 2 | **Privasi Non-Owner** | `GET /api/items` (Login user lain) | HTTP 200, kunci kontak pada item milik user lain **omitted** |
| 3 | **Akses Kontak Owner** | `GET /api/items/{id}` (Login owner) | HTTP 200, kunci kontak disertakan lengkap |
| 4 | **Proteksi Laporan Admin** | `GET /api/item-reports` (Login user biasa) | HTTP 403 Forbidden |
| 5 | **Anti Self-Reporting** | `POST /api/items/{id}/report` (Owner melapor sendiri) | HTTP 403 Forbidden |
| 6 | **Anti Duplicate Report** | `POST /api/items/{id}/report` (User lapor 2x pending) | HTTP 409 Conflict |
| 7 | **Barang Expired Tidak Boleh Dilapor** | `POST /api/items/{id}/report` (Item expired > 72 jam) | HTTP 409 Conflict |
| 8 | **Anti Double Resolution (Paralel)** | `PATCH /api/item-reports/{id}/status` (2 laporan pada item yang sama) | Laporan ke-2 ditolak HTTP 409 Conflict via DB Lock |
| 9 | **Persetujuan Laporan Admin** | `PATCH /api/item-reports/{id}/status` (`accepted`) | Report status `accepted`, Item status `resolve_requested` |
| 10 | **Penolakan Resolusi Owner** | `PATCH /api/items/{id}/status` (Owner tolak resolusi) | Item kembali `approved` (waktu tetap), Report menjadi `closed` |
| 11 | **Batas Waktu Repost** | `POST /api/items/{id}/repost` (Belum 72 jam) | HTTP 409 Conflict |
| 12 | **Resubmit Barang Ditolak** | `POST /api/items/{id}` (`_method: PUT` pada `rejected`) | Item berhasil diupdate dan status otomatis kembali `pending` |

---

## 7. Checklist Bukti Laporan Resmi (PDF)

> 📌 **Catatan Penyelarasan Bukti:** Detail penamaan kode bukti (SS-01 s.d. SS-14), lokasi URL/CLI, dan elemen visual kunci secara operasional mengikuti tabel matriks screenshot pada root `README.md`.

| No | Komponen Bukti | Kriteria & Deskripsi Tangkapan Layar (Screenshot) |
| :-: | :--- | :--- |
| 1 | **Branch Protection (SS-01)** | Screenshot menu `Settings > Branches` menampilkan aturan proteksi branch `main` & `dev` |
| 2 | **Automated Tests (SS-02)** | Screenshot terminal `php artisan test` membuktikan seluruh 12 Feature Test lulus (hijau) |
| 3 | **Postman Collection (SS-03)** | Screenshot koleksi 14 endpoint & response representatif di aplikasi Postman |
| 4 | **CI/CD Pipeline (SS-11 & SS-12)** | Screenshot tab GitHub Actions menampilkan alur CI & CD yang berjalan sukses (hijau) |
| 5 | **GitHub Packages (SS-13)** | Screenshot halaman Packages menampilkan image Backend dan Frontend ber-tag Commit SHA |
| 6 | **Output Docker Compose (SS-08)** | Screenshot keluaran CLI `docker compose ps` menampilkan 3 service (DB, BE, FE) berstatus `Up` |
| 7 | **Log FrankenPHP (SS-09)** | Screenshot log container backend (`docker compose logs backend`) bukti server FrankenPHP |
| 8 | **Persistensi Data (SS-10)** | Screenshot bukti data DB dan storage foto tetap utuh setelah container di-*rebuild* |
| 9 | **Arsitektur Alur Data** | Diagram dan penjelasan naratif aliran data (Vue.js $\rightarrow$ Nginx $\rightarrow$ FrankenPHP $\rightarrow$ MySQL) |
| 10 | **Kendala & Resolusi** | Narasi kendala teknis yang dihadapi selama pengerjaan beserta solusi teknisnya |
