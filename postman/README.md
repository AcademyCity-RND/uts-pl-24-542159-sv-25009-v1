# 📬 API Testing Collection — JejakBarang (Postman)

Direktori ini memuat berkas ekspor **Postman Collection** dan **Postman Environment** yang digunakan untuk memverifikasi fungsionalitas seluruh endpoint RESTful API Backend sistem **JejakBarang** secara otomatis.

---

## 📁 Berkas Tersedia
1. `uts-pl-api.postman_collection.json`: Berisi daftar seluruh permintaan HTTP (Auth, Public Browsing, CRUD `lost_items`, Status Approval & Resolusi).
2. `uts-pl-local.postman_environment.json`: Berisi variabel lingkungan pengujian lokal (`base_url` dan `token`).

---

## ⚙️ Variabel Lingkungan (Environment Variables)

| Variabel | Nilai Default | Keterangan |
| :--- | :--- | :--- |
| `base_url` | `http://localhost:8000/api` | Alamat basis API backend |
| `token` | *(Otomatis terisi saat Login)* | Bearer authentication token hasil respons login |

---

## 🚀 Alur Pengujian & Automasi Token Sanctum

Collection ini dilengkapi dengan skrip pengujian otomatis (*Post-response / Tests Scripts*):
1. **Login Request (`POST /api/login`)**:
   Saat request login berhasil mengembalikan respons `200 OK`, skrip pengujian di tab *Tests* akan secara otomatis mengekstrak token dari payload JSON dan menyimpannya ke variabel environment:
   ```javascript
   if (pm.response.code === 200) {
       var jsonData = pm.response.json();
       pm.environment.set("token", jsonData.token);
   }
   ```
2. **Endpoint Terproteksi (CRUD Resource & Logout)**:
   Seluruh endpoint terproteksi dikonfigurasikan menggunakan tipe autentikasi **Bearer Token** yang merujuk pada `{{token}}`. Dengan demikian, pengujian dapat dijalankan berurutan tanpa perlu menyalin token secara manual.

---

## 🧪 Urutan Skenario Pengujian (Test Scenarios)

1. `POST /api/login` (Kredensial Salah $\rightarrow$ Ekspektasi: `401 Unauthorized` / `422 Unprocessable Entity`).
2. `POST /api/login` (Kredensial Valid $\rightarrow$ Ekspektasi: `200 OK`, token tersimpan otomatis di `{{token}}`).
3. `GET /api/public/items` (Browsing Publik Tamu $\rightarrow$ Ekspektasi: `200 OK`, hanya barang `approved` aktif < 3 hari).
4. `GET /api/items` (Mengambil daftar data barang terproteksi $\rightarrow$ Ekspektasi: `200 OK`).
5. `POST /api/items` (Validasi Gagal / Field Kosong $\rightarrow$ Ekspektasi: `422 Unprocessable Entity`).
6. `POST /api/items` (Tambah Laporan Baru + Upload Gambar `multipart/form-data` $\rightarrow$ Ekspektasi: `201 Created`, status awal: `pending`).
7. `GET /api/items/{id}` (Melihat detail data barang yang baru dibuat $\rightarrow$ Ekspektasi: `200 OK`).
8. `POST /api/items/{id}` *(dengan `_method: PUT`)* (Pembaruan data barang & foto baru $\rightarrow$ Ekspektasi: `200 OK`).
9. `PATCH /api/items/{id}/status` (Admin menyetujui laporan menjadi `approved` $\rightarrow$ Ekspektasi: `200 OK`).
10. `PATCH /api/items/{id}/status` (User menyelesaikan barang menjadi `resolved` $\rightarrow$ Ekspektasi: `200 OK`).
11. `DELETE /api/items/{id}` (Menghapus laporan barang $\rightarrow$ Ekspektasi: `200 OK`).
12. `POST /api/logout` (Revokasi token aktif $\rightarrow$ Ekspektasi: `200 OK`).
13. `GET /api/items` (Akses kembali setelah logout tanpa token $\rightarrow$ Ekspektasi: `401 Unauthorized`).

---

## 📥 Cara Import ke Aplikasi Postman
1. Buka aplikasi **Postman**.
2. Klik tombol **Import** di pojok kiri atas.
3. Seret (*drag and drop*) kedua file `.json` dari folder `/postman` ini ke dalam jendela import.
4. Pilih environment **UTS-PL Local** di pojok kanan atas aplikasi Postman sebelum menjalankan pengujian.
