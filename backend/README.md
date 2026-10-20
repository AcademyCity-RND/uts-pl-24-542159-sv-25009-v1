# ⚙️ JejakBarang — Backend REST API Service

> **Layanan:** Backend RESTful API murni (JSON)  
> **Teknologi:** Laravel 11.x, FrankenPHP (PHP 8.3), MySQL 8.x, Laravel Sanctum  
> **Arsitektur:** Decoupled Monorepo Service

---

## 1. Ikhtisar Layanan

Layanan Backend ini bertindak sebagai penyedia API tunggal (*Single Source of Truth*) untuk platform **JejakBarang**. Dibangun dengan performa tinggi di atas server aplikasi **FrankenPHP**, backend ini menangani seluruh logika bisnis: autentikasi token personal (Sanctum), siklus validasi alur *"User Propose $\rightarrow$ Admin Approve"*, filtrasi masa aktif pasif (TTL 72 jam), pencegahan manipulasi status barang, otorisasi berbasis hak akses (Policies & Gates), hingga proteksi konkurensi data laporan penemuan pihak ketiga.

---

## 📌 Status Implementasi Fitur Backend (Fase 2)

- [x] **Step 2.1:** Setup Laravel 11 & instalasi paket `laravel/sanctum` (termasuk migrasi token Sanctum & trait `HasApiTokens`).
- [x] **Step 2.2:** Rancang skema database & index: Migrasi tabel `users` (tambah role & phone), `lost_items` (+composite index `status, approved_at`), dan `item_reports` (+composite index `lost_item_id, status`).
- [x] **Step 2.3:** Pembuatan Model Eloquent `User`, `LostItem`, dan `ItemReport` dengan proteksi Mass Assignment (`$guarded`).
- [x] **Step 2.4:** FormRequest Validasi & Rate Limiting: `RegisterRequest`, `LoginRequest`, `StoreLostItemRequest`, `UpdateLostItemRequest`, dan `StoreItemReportRequest`.
- [x] **Step 2.5:** Implementasi endpoint autentikasi: `POST /api/register`, `POST /api/login`, dan `POST /api/logout`.
- [x] **Step 2.6:** Implementasi rute publik `GET /api/public/items` (status `approved`, < 72 jam, omit kontak).
- [x] **Step 2.7:** Implementasi rute CRUD terproteksi (`auth:sanctum`): `GET /api/items`, `GET /api/items/{id}`, `POST /api/items`, `POST /api/items/{id}` (`_method: PUT`), `DELETE /api/items/{id}`, dan `PATCH /api/items/{id}/status`.
- [x] **Step 2.8:** Implementasi alur resolusi & laporan pihak ketiga: `POST /api/items/{id}/report` (anti-duplicate pending), `POST /api/items/{id}/repost`, serta `GET`/`PATCH` `/api/item-reports` khusus Admin dengan Dual Pessimistic Locking (`lockForUpdate()`).
- [x] **Step 2.9:** Implementasi `LostItemPolicy` dan `ItemReportPolicy` untuk otorisasi akses (Respons 403 Forbidden).
- [x] **Step 2.10:** Penulisan 12 Feature Test otomatis berbasis MySQL testing engine (`uts_kepl_test`).
- [ ] **Step 2.11:** Konfigurasi runner server aplikasi FrankenPHP.
- [ ] **Step 2.12:** Ekspor Postman Collection (14 endpoint lengkap) & Environment ke folder `/postman`.

---

## 2. Ketentuan Teknis & Panduan Kepatuhan Soal

1. **Aplikasi Server FrankenPHP:**
   * Beroperasi menggantikan peran tradisional PHP-FPM dan Nginx pada kontainer backend.
   * Memberikan respons HTTP cepat dengan *worker mode* bawaan FrankenPHP.
2. **Kepatuhan Autentikasi Token (Soal 1):**
   * Seluruh endpoint operasional CRUD dan aksi status dilindungi middleware `auth:sanctum`.
   * Pengecualian hanya diberikan pada endpoint login/register publik serta endpoint khusus penjelajahan tamu (`GET /api/public/items`).
3. **Penyelarasan HTTP Method Spoofing (`PUT`):**
   * Mengatasi batasan native stream PHP pada request `PUT` dengan `multipart/form-data`.
   * Pembaruan data foto dari Frontend dikirim via HTTP `POST` dengan parameter `_method: 'PUT'`.
4. **Proteksi Konkurensi & Integritas Status:**
   * Menggunakan **Dual Pessimistic Locking** (`lockForUpdate()`) di dalam `DB::transaction()` untuk mencegah persetujuan ganda laporan penemuan pihak ketiga.
   * Menolak manipulasi status langsung `resolve_requested` dan `closed` via endpoint publik/umum.

---

## 3. Matriks Endpoint API Resmi

| Method | Endpoint | Akses & Middleware | Deskripsi & Validasi Utama |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/register` | Publik (`throttle:5,1`) | Pendaftaran pengguna baru. Role di-hardcode `user`. Return token langsung. |
| `POST` | `/api/login` | Publik (`throttle:5,1`) | Autentikasi email & password, menerbitkan Bearer Token Sanctum. |
| `POST` | `/api/logout` | `auth:sanctum` | Merevokasi token aktif pengguna saat ini. |
| `GET` | `/api/public/items` | Publik (Guest) | Daftar barang aktif (`approved`, < 72 jam). Field kontak **omitted**. |
| `GET` | `/api/items` | `auth:sanctum` | Daftar barang authenticated. Filter `?status=...&page=1&per_page=10`. |
| `GET` | `/api/items/{id}` | `auth:sanctum` | Detail barang lengkap. Field kontak hanya untuk owner & admin. |
| `POST` | `/api/items` | `auth:sanctum` | Buat laporan barang baru (otomatis `pending`, upload foto `FormData`). |
| `POST` | `/api/items/{id}` | `auth:sanctum` | Update barang (`_method: 'PUT'`). Jika status `rejected`, auto-reset `pending`. |
| `DELETE`| `/api/items/{id}` | `auth:sanctum` | Menghapus barang (hanya pemilik atau admin). Return 200 OK. |
| `PATCH`| `/api/items/{id}/status` | `auth:sanctum` | Update status barang (`approved`, `rejected`, `resolved`). |
| `POST` | `/api/items/{id}/report` | `auth:sanctum` (`throttle:10,1`) | Lapor penemuan pihak ketiga (non-owner). Anti-duplicate pending. |
| `POST` | `/api/items/{id}/repost` | `auth:sanctum` | Repost barang expired/resolved (reset waktu 72 jam, max 1x per 72 jam). |
| `GET` | `/api/item-reports` | `auth:sanctum` (Admin Only) | Review seluruh laporan pihak ketiga masuk (`pending`, `accepted`, dll). |
| `PATCH`| `/api/item-reports/{id}/status`| `auth:sanctum` (Admin Only) | Terima/Tolak laporan penemuan (`accepted` $\rightarrow$ trigger `resolve_requested`). |

---

## 4. Struktur Respons Standar

### Respons Sukses Login (`POST /api/login` - `200 OK`):
```json
{
  "message": "Login berhasil.",
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

### Respons Sukses Register (`POST /api/register` - `201 Created`):
```json
{
  "message": "Pendaftaran berhasil.",
  "data": {
    "user": {
      "id": 2,
      "name": "Mahasiswa Baru",
      "email": "maba@example.com",
      "role": "user"
    },
    "token": "<BEARER_TOKEN>"
  }
}
```

### Respons Sukses Resource Item (`200 OK` / `201 Created`):
```json
{
  "message": "Operasi berhasil diselesaikan.",
  "data": {
    "id": 1,
    "item_name": "Dompet Kulit Cokelat",
    "category": "lost",
    "status": "approved",
    "expires_at": "2026-10-23T02:00:00Z",
    "is_expired": false,
    "remaining_seconds": 259200
  }
}
```

### Respons Error Terstruktur (`401`, `403`, `404`, `409`, `422`, `429`):
```json
{
  "message": "Validasi input gagal atau terjadi konflik status.",
  "errors": {
    "item_name": ["Nama barang wajib diisi."],
    "image": ["Berkas gambar maksimal 2048 KB."]
  }
}
```

---

## 5. Skema Migrasi Basis Data & Indexing

1. **Tabel `users`:**
   * Kolom: `id`, `name`, `email` (unique), `password`, `role` (`admin`/`user`), `phone_number`, `timestamps`.
2. **Tabel `lost_items`:**
   * Kolom: `id`, `user_id` (FK), `item_name`, `category` (`lost`/`found`), `location`, `current_storage`, `contact_info`, `reporter_contact_info`, `chronology`, `status` (`pending`/`approved`/`rejected`/`resolve_requested`/`resolved`), `image_path`, `approved_at`, `resolved_at`, `timestamps`.
   * **Index:** `$table->index(['status', 'approved_at']);` untuk akselerasi kueri publik.
3. **Tabel `item_reports`:**
   * Kolom: `id`, `lost_item_id` (FK cascade), `reporter_user_id` (FK restrict), `message`, `status` (`pending`/`accepted`/`rejected`/`closed`), `timestamps`.
   * **Index:** `$table->index(['lost_item_id', 'status']);` dan `$table->index(['reporter_user_id', 'lost_item_id', 'status']);`.

---

## 6. Standar Pengujian Otomatis (*Feature Testing Suite*)

> ⚠️ **Keputusan Arsitektur Basis Data Pengujian:**  
> Seluruh skenario pengujian Feature Test **wajib dieksekusi terhadap basis data MySQL** (menggunakan database uji `uts_kepl_test` atau instance MySQL Docker), **BUKAN SQLite in-memory**. Hal ini mutlak diperlukan karena SQLite tidak mengimplementasikan semantik *pessimistic row-level locking* (`lockForUpdate()`) dan isolasi transaksi ACID yang identik dengan mesin penyimpanan MySQL InnoDB.
>
> Contoh konfigurasi berkas `.env.testing`:
> ```env
> DB_CONNECTION=mysql
> DB_HOST=127.0.0.1
> DB_PORT=3306
> DB_DATABASE=uts_kepl_test
> DB_USERNAME=root
> DB_PASSWORD=
> ```

Pengujian otomatis dijalankan melalui PHPUnit dengan target **100% kelulusan** pada 12 skenario wajib:
1. `test_guest_cannot_see_contact_info_on_public_list()`
2. `test_authenticated_non_owner_cannot_see_other_user_contact_info()`
3. `test_owner_and_admin_can_see_contact_info()`
4. `test_non_admin_cannot_access_item_reports_inbox()`
5. `test_owner_cannot_report_own_item()`
6. `test_user_cannot_create_duplicate_pending_report()`
7. `test_expired_item_cannot_be_reported()`
8. `test_parallel_report_acceptance_prevents_double_resolution_via_db_lock()`
9. `test_admin_accepting_report_triggers_resolve_requested_status()`
10. `test_owner_rejecting_resolution_sets_report_to_closed_and_keeps_approved_at()`
11. `test_repost_before_72_hours_returns_409_conflict()`
12. `test_editing_rejected_item_resets_status_to_pending()`

Jalankan pengujian via CLI:
```bash
# Eksekusi di dalam container backend (MySQL terhubung otomatis)
docker compose exec backend php artisan test

# Atau eksekusi lokal dengan basis data MySQL lokal (pastikan database uts_kepl_test telah dibuat)
php artisan test
```

---

## 7. Instruksi Setup & Eksekusi Server
 
 ### A. Eksekusi Resmi Produksi (FrankenPHP via Docker Compose — Rekomendasi Utama)
 Layanan backend secara resmi beroperasi di atas server aplikasi **FrankenPHP**:
 ```bash
 # 1. Bangun dan jalankan container backend bersama stack
 docker compose up -d --build
 
 # 2. Verifikasi log untuk membuktikan request dilayani runtime FrankenPHP
 docker compose logs backend
 ```
 
 ### B. Eksekusi Cepat Lokal (Quick Local Development — Non-Docker)
 Jika ingin menjalankan pengembangan lokal tanpa Docker:
 ```bash
 # 1. Salin berkas lingkungan dan konfigurasi:
 cp .env.example .env
 composer install
 php artisan key:generate
 
 # 2. Jalankan migrasi dan seeder kredensial demo:
 php artisan migrate:fresh --seed
 # Akun Admin: admin@jejakbarang.test / password123
 # Akun Mahasiswa: mahasiswa@jejakbarang.test / password123
 
 # 3. Tautkan penyimpanan publik untuk foto barang:
 php artisan storage:link
 
 # 4. Jalankan development server lokal:
 php artisan serve --port=8000
 ```
