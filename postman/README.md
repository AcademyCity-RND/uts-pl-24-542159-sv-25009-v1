# 📮 Koleksi Pengujian API Postman — JejakBarang

Repositori ini menyediakan berkas koleksi Postman resmi (**`uts-pl-api.postman_collection.json`**) dan environment (**`uts-pl-local.postman_environment.json`**) untuk menguji seluruh **14 endpoint** RESTful API **JejakBarang** secara menyeluruh dan terotomatisasi.

---

## ⚙️ Variabel Environment Postman

| Variabel | Nilai Default | Keterangan |
| :--- | :--- | :--- |
| `base_url` | `http://localhost:8000/api` | Alamat basis API backend |
| `token` | *(Otomatis terisi saat Login / Register)* | Bearer authentication token hasil respons API |

---

## 🚀 Alur Pengujian & Automasi Token Sanctum

Collection ini dilengkapi dengan skrip pengujian otomatis (*Post-response / Tests Scripts*):
1. **Login & Register Request (`POST /api/login` & `POST /api/register`)**:
   Saat request login atau register berhasil mengembalikan respons `200 OK` atau `201 Created`, skrip pengujian di tab *Tests* akan secara otomatis mengekstrak token dari payload JSON terstruktur dan menyimpannya ke variabel environment:
   ```javascript
   if (pm.response.code === 200 || pm.response.code === 201) {
       var jsonData = pm.response.json();
       pm.environment.set("token", jsonData.data.token);
   }
   ```
2. **Endpoint Terproteksi (CRUD Resource, Laporan & Logout)**:
   Seluruh endpoint terproteksi dikonfigurasikan menggunakan tipe autentikasi **Bearer Token** yang merujuk pada `{{token}}` (dengan header `Authorization: Bearer <BEARER_TOKEN>`). Dengan demikian, pengujian dapat dijalankan berurutan tanpa perlu menyalin token secara manual.

---

## 🧪 Urutan 14 Skenario Pengujian (Complete Endpoint Scenarios)

1. `POST /api/register` (Pendaftaran user baru $\rightarrow$ Ekspektasi: `201 Created`, role: `user`, token tersimpan).
2. `POST /api/login` (Kredensial Salah $\rightarrow$ Ekspektasi: `401 Unauthorized`).
3. `POST /api/login` (Kredensial Valid $\rightarrow$ Ekspektasi: `200 OK`, token tersimpan di `{{token}}`).
4. `GET /api/public/items` (Browsing Publik Tamu $\rightarrow$ Ekspektasi: `200 OK`, barang `approved` aktif < 72 jam, field kontak *omitted*).
5. `GET /api/items` (Daftar barang authenticated $\rightarrow$ Ekspektasi: `200 OK`, mendukung paginasi & filter).
6. `POST /api/items` (Validasi Gagal / Field Kosong $\rightarrow$ Ekspektasi: `422 Unprocessable Entity`).
7. `POST /api/items` (Tambah Laporan Barang Baru + Upload Gambar `multipart/form-data` $\rightarrow$ Ekspektasi: `201 Created`, status awal: `pending`).
8. `GET /api/items/{id}` (Detail barang lengkap $\rightarrow$ Ekspektasi: `200 OK`, kontak hanya tampil ke owner/admin).
9. `POST /api/items/{id}` *(dengan `_method: PUT`)* (Pembaruan data barang & foto baru $\rightarrow$ Ekspektasi: `200 OK`, jika `rejected` auto-reset `pending`).
10. `POST /api/items/{id}/report` (Pelaporan penemuan oleh pihak ketiga $\rightarrow$ Ekspektasi: `201 Created` / `409 Conflict` jika duplikat pending).
11. `GET /api/item-reports` (Admin review seluruh laporan pihak ketiga $\rightarrow$ Ekspektasi: `200 OK` untuk admin, `403 Forbidden` untuk user biasa).
12. `PATCH /api/item-reports/{id}/status` (Admin menyetujui laporan $\rightarrow$ Ekspektasi: `200 OK`, trigger item menjadi `resolve_requested`).
13. `PATCH /api/items/{id}/status` (Owner menerima resolusi $\rightarrow$ Ekspektasi: `200 OK`, item menjadi `resolved`, report pending lain auto-`closed`).
14. `POST /api/items/{id}/repost` (Owner repost barang expired/resolved $\rightarrow$ Ekspektasi: `200 OK`, reset 72 jam; `409 Conflict` jika belum habis).
15. `DELETE /api/items/{id}` (Menghapus laporan barang $\rightarrow$ Ekspektasi: `200 OK`).
16. `POST /api/logout` (Revokasi token aktif $\rightarrow$ Ekspektasi: `200 OK`).

---

## 📸 Catatan Bukti Tangkapan Layar (SS-03)
Tangkapan layar untuk bukti laporan **SS-03** menampilkan request representatif (Auth Login/Register, CRUD Item, Report Pihak Ketiga, dan Resolution Approval) beserta kode status semantik (`200`, `201`, `401`, `403`, `404`, `409`, `422`, `429`). Seluruh request lengkap tersimpan di berkas JSON koleksi repositori ini.
