# 📮 Koleksi Pengujian API Postman — JejakBarang

Repositori ini menyediakan berkas koleksi Postman resmi (**`uts-pl-api.postman_collection.json`**) dan environment (**`uts-pl-local.postman_environment.json`**) untuk menguji **15 operasi endpoint** melalui **18 request** RESTful API **JejakBarang** secara menyeluruh, terotomatisasi, dan siap pakai.

---

## ⚙️ Variabel Environment Postman (`uts-pl-local.postman_environment.json`)

| Variabel | Nilai Default | Tipe | Keterangan |
| :--- | :--- | :--- | :--- |
| `base_url` | `http://localhost:8000/api` | default | Alamat basis API backend |
| `token` | *(Otomatis terisi saat login user)* | secret | Bearer token mahasiswa/user biasa |
| `admin_token` | *(Otomatis terisi saat login admin)* | secret | Bearer token administrator |
| `item_id` | `1` | default | ID acuan resource barang (`/items/{id}`) |
| `report_id` | `1` | default | ID acuan resource laporan (`/item-reports/{id}`) |

---

## 👥 Data Akun & Dummy Seeder Siap Pakai

Sebelum melakukan pengujian, pastikan database sudah terisi data bawaan dengan menjalankan seeder:
```bash
php artisan migrate:fresh --seed
```

Kredensial akun demo yang tersedia:

| Role | Nama | Email | Password | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **Admin** | Administrator Kampus | `admin@jejakbarang.test` | `password123` | Akses endpoint review admin, moderasi item & laporan |
| **User (Owner)** | Hilarius Mahasiswa | `mahasiswa@jejakbarang.test` | `password123` | Pemilik barang demo utama |
| **User (Finder)** | Rian Penemu | `penemu@jejakbarang.test` | `password123` | Pelapor pihak ketiga |
| **User lain** | Budi Santoso | `budi@jejakbarang.test` | `password123` | User biasa untuk pengujian akses |

---

## 🚀 Alur Pengujian & Automasi Token Sanctum

Collection ini dilengkapi dengan skrip pengujian otomatis (*Post-response / Tests Scripts*):

1. **Login User Mahasiswa (`POST /api/login`)**:
   - Skrip *Tests* otomatis mengekstrak token respons dan menyimpannya ke `{{token}}`.
   - Request berelasi berikutnya (`GET /api/items`, `POST /api/items`, dll.) otomatis terautentikasi tanpa copy-paste manual.
2. **Login Admin (`POST /api/login`)**:
   - Skrip *Tests* otomatis mengekstrak token admin dan menyimpannya ke `{{admin_token}}`.
   - Digunakan otomatis pada endpoint admin seperti `GET /api/item-reports` dan `PATCH /api/item-reports/{id}/status`.
3. **Penyimpanan ID Dinamis**:
   - Saat membuat item baru (`POST /api/items`) atau submit laporan (`POST /api/items/{id}/report`), ID yang dibuat otomatis memperbarui `{{item_id}}` dan `{{report_id}}`.

---

## 🧪 Daftar Request dalam Postman Collection

Collection saat ini berisi **18 request** yang mencakup **15 operasi
endpoint**. Jalankan request per skenario state secara terpisah; request
`Delete Item` tidak boleh dijalankan sebelum request Report/Resolution yang
masih memakai `{{item_id}}`.

| No | Request Name | Method & Endpoint | Auth | Ekspektasi Status | Deskripsi Singkat |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 1 | Register User Baru | `POST /api/register` | None | `201 Created` | Pendaftaran akun mahasiswa baru dengan validasi password & domain `@gmail.com` |
| 2 | Login Mahasiswa (Valid) | `POST /api/login` | None | `200 OK` | Login akun demo Budi Santoso, otomatis simpan ke `{{token}}` |
| 3 | Login Admin (Valid) | `POST /api/login` | None | `200 OK` | Login akun demo Admin, otomatis simpan ke `{{admin_token}}` |
| 4 | Browsing Publik Tamu | `GET /api/public/items` | None | `200 OK` | Menampilkan item `approved` aktif < 72 jam, nomor kontak disembunyikan |
| 5 | Daftar Barang Saya | `GET /api/items` | Bearer `{{token}}` | `200 OK` | Menampilkan item milik user login beserta paginasi & filter |
| 6 | Tambah Barang Hilang Baru | `POST /api/items` | Bearer `{{token}}` | `201 Created` | Upload multipart form-data + gambar, status awal otomatis `pending` |
| 7 | Detail Barang | `GET /api/items/{{item_id}}` | Bearer `{{token}}` | `200 OK` | Menampilkan detail barang, nomor kontak terbuka untuk pemilik/admin |
| 8 | Update Data Barang | `POST /api/items/{{item_id}}` | Bearer `{{token}}` | `200 OK` | Update data via form-data (`_method: PUT`) + upload file gambar baru |
| 9 | Buat Laporan Pihak Ketiga | `POST /api/items/{{item_id}}/report` | Bearer `{{token}}` | `201 Created` | User non-owner mengajukan laporan klaim/penemuan (menggunakan token admin pada collection demo), otomatis simpan `{{report_id}}` |
| 10 | Admin List Item Reports | `GET /api/item-reports` | Bearer `{{admin_token}}` | `200 OK` | Review daftar laporan pihak ketiga (hanya dapat diakses role admin) |
| 11 | Admin Verifikasi Laporan | `PATCH /api/item-reports/{{report_id}}/status` | Bearer `{{admin_token}}` | `200 OK` | Admin menyetujui laporan (`accepted`), trigger item jadi `resolve_requested` |
| 12 | Owner Verifikasi Resolusi | `PATCH /api/items/{{item_id}}/status` | Bearer `{{token}}` | `200 OK` | Pemilik menyetujui klaim, status item jadi `resolved`, laporan lain `closed` |
| 13 | Owner Repost Barang | `POST /api/items/{{item_id}}/repost` | Bearer `{{token}}` | `200 OK` | Perpanjangan 72 jam jika item expired / selesai cooldown |
| 14 | Hapus Laporan Barang | `DELETE /api/items/{{item_id}}` | Bearer `{{token}}` | `200 OK` | Menghapus barang milik user |
| 15 | Logout User | `POST /api/logout` | Bearer `{{token}}` | `200 OK` | Revokasi token Sanctum saat ini |

---

## 🌐 Cara Menjalankan di Postman Web vs Postman Desktop

### 1. Menggunakan Postman Desktop App (Rekomendasi)
1. Buka aplikasi **Postman Desktop**.
2. Klik tombol **Import** di kiri atas.
3. Tarik atau pilih kedua berkas:
   - `postman/uts-pl-api.postman_collection.json`
   - `postman/uts-pl-local.postman_environment.json`
4. Pilih environment aktif di pojok kanan atas: **JejakBarang Local**.
5. Jalankan request per skenario state; jangan menjalankan seluruh collection secara linear karena beberapa request mengubah atau menghapus resource yang dipakai request berikutnya.

### 2. Menggunakan Postman Web (Browser)
Karena backend berjalan di mesin lokal (`http://localhost:8000`), browser membatasi CORS/akses local network secara default.
Agar Postman Web dapat mengirim request ke `localhost`:
1. Unduh dan jalankan **Postman Desktop Agent** (tersedia gratis di [postman.com/downloads/agent](https://www.postman.com/downloads/agent/)).
2. Pada status bar di bagian bawah jendela Postman Web, pastikan agent selector beralih dari **Cloud Agent** ke **Postman Desktop Agent**.
3. Import koleksi dan environment, pilih **JejakBarang Local**, lalu jalankan request.

---

## 📸 Panduan Pengambilan Tangkapan Layar (SS-03)

Untuk kebutuhan bukti tangkapan layar **SS-03** pada laporan UTS:
1. Jalankan request **Login Mahasiswa (Valid)** atau **Login Admin (Valid)** hingga status `200 OK`.
2. Jalankan request utama seperti **Browsing Publik Tamu** (`GET /api/public/items`) atau **Detail Barang** (`GET /api/items/1`).
3. Tangkap layar jendela Postman yang memperlihatkan:
   - Method dan URL Endpoint (misal `GET http://localhost:8000/api/public/items`).
   - Status kode respons (`200 OK` / `201 Created`).
   - JSON response body terstruktur dengan properti `message` dan `data` sesuai kontrak API.
