# 📬 API Testing Collection — Postman

Direktori ini memuat berkas ekspor **Postman Collection** dan **Postman Environment** yang digunakan untuk memverifikasi fungsionalitas seluruh endpoint RESTful API Backend secara otomatis.

---

## 📁 Berkas Tersedia
1. `uts-pl-api.postman_collection.json`: Berisi daftar seluruh permintaan HTTP (Auth & CRUD Resource).
2. `uts-pl-local.postman_environment.json`: Berisi variabel lingkungan pengujian lokal (`base_url` dan `token`).

---

## ⚙️ Variabel Lingkungan (Environment Variables)

| Variabel | Nilai Default | Keterangan |
| :--- | :--- | :--- |
| `base_url` | `http://localhost:8000/api` | Alamat basis API backend |
| `token` | *(Otomatis terisi saat Login)* | Bearer authentication token |

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

## 🧪 Urutan Eksekusi Pengujian (Test Scenarios)

1. `POST /api/login` (Kasus Kredensial Salah $\rightarrow$ Ekspektasi: `401 Unauthorized` / `422 Unprocessable Entity`).
2. `POST /api/login` (Kasus Kredensial Valid $\rightarrow$ Ekspektasi: `200 OK`, token tersimpan otomatis).
3. `GET /api/items` (Mengambil semua data $\rightarrow$ Ekspektasi: `200 OK`).
4. `POST /api/items` (Validasi Gagal/Field Kosong $\rightarrow$ Ekspektasi: `422 Unprocessable Entity`).
5. `POST /api/items` (Data Valid $\rightarrow$ Ekspektasi: `201 Created`).
6. `GET /api/items/{id}` (Melihat detail data yang baru dibuat $\rightarrow$ Ekspektasi: `200 OK`).
7. `PUT /api/items/{id}` (Memperbarui data $\rightarrow$ Ekspektasi: `200 OK`).
8. `DELETE /api/items/{id}` (Menghapus data $\rightarrow$ Ekspektasi: `200 OK`).
9. `POST /api/logout` (Revokasi token $\rightarrow$ Ekspektasi: `200 OK`).
10. `GET /api/items` (Mencoba akses kembali setelah logout $\rightarrow$ Ekspektasi: `401 Unauthorized`).

---

## 📥 Cara Import ke Aplikasi Postman
1. Buka aplikasi **Postman**.
2. Klik tombol **Import** di pojok kiri atas.
3. Seret (*drag and drop*) kedua file `.json` dari folder `/postman` ini ke dalam jendela import.
4. Pilih environment **UTS-PL Local** di pojok kanan atas aplikasi Postman sebelum menjalankan pengujian.
