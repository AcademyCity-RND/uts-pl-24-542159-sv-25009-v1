# 🩺 Panduan Observability, Structured Logging & Health Check (P10)
## Laporan Praktikum Pertemuan 10 — Konstruksi & Evolusi Perangkat Lunak
**Hilarius Christiano Avin Paliling (NIM: 24/542159/SV/25009)**  
*Program Studi Sarjana Terapan Rekayasa Perangkat Lunak, Departemen TEDI, Sekolah Vokasi, Universitas Gadjah Mada*  
*Repositori GitHub:* [https://github.com/AcademyCity-RND/uts-pl-24-542159-sv-25009-v1](https://github.com/AcademyCity-RND/uts-pl-24-542159-sv-25009-v1)  
*Branch Fitur:* `feature/logging-healthcheck`

---

## 📌 Ringkasan Implementasi Tugas P10

Dokumen ini memuat panduan komprehensif, justifikasi akademik, dan template laporan resmi `P10_542159_Hilarius Christiano Avin Paliling.pdf` untuk memenuhi kriteria penilaian Praktikum 10:
1. **Stdout Logging (12-Factor App):** Konfigurasi `LOG_CHANNEL=stderr` agar log Laravel langsung mengalir ke container stdout/stderr.
2. **3 Log Terstruktur Berkonteks:** Pencatatan log terstruktur level `INFO`, `WARNING`, dan `ERROR` dengan aktor, aksi, dan alasan (zero-leak sensitive data).
3. **Endpoint `/health`:** Pengecekan riil terhadap koneksi MySQL (`getPdo()`) dan Cache (`put`/`get`), mengembalikan HTTP 200 saat sehat atau 503 saat bermasalah.
4. **Healthcheck Dua Arah pada Compose:** Konfigurasi `healthcheck` backend di `compose.yaml` (`interval`, `timeout`, `retries`, `start_period`) yang memantau status secara dinamis.
5. **Simulasi DB Mati & Pemulihan:** Pembuktian status backend berubah menjadi `unhealthy` saat MySQL dimatikan, mencatat log error, dan kembali `healthy` saat MySQL dipulihkan.
6. **Pembatasan Ukuran Log:** Penerapan log rotation dengan driver `json-file`, `max-size: "10m"`, dan `max-file: "3"`.

---

## 🛠️ Rincian Implementasi 6 Poin Tugas

### 1. Pengaturan Log Laravel ke Stdout / Stderr
* **Permasalahan Tradisional:** Secara bawaan, Laravel menyimpan log ke berkas lokal `storage/logs/laravel.log`. Di dalam arsitektur kontainer, berkas ini terisolasi di dalam layer kontainer dan menyulitkan agregasi log terpusat.
* **Solusi & Konfigurasi:**  
  Mengubah channel log bawaan menjadi stream standard error (`php://stderr`) melalui variabel lingkungan:
  ```env
  LOG_CHANNEL=stderr
  LOG_LEVEL=debug
  ```
  Pada server aplikasi FrankenPHP / Caddy, stream `stderr` langsung dialirkan ke output kontainer Docker, sehingga seluruh aktivitas log aplikasi dapat dipantau langsung melalui perintah standar:
  ```bash
  docker compose logs -f backend
  ```

---

### 2. Tiga Log Terstruktur Berkonteks (Info, Warning, Error)

Setiap log mencatat konteks lengkap: **siapa (aktor)**, **sedang mengerjakan apa (aksi)**, dan **sebabnya (alasan)** tanpa pernah mencatat *password*, *token*, atau informasi kontak pribadi:

#### A. Level `INFO` — Alur Penyimpanan Data Laporan Barang Baru
* **Lokasi Berkas:** `backend/app/Http/Controllers/Api/ItemController.php` (method `store()`)
* **Alasan Pemilihan Alur:** Pembuatan laporan barang hilang/temuan adalah transaksi bisnis inti sistem JejakBarang. Mencatat log saat data berhasil tersimpan ke basis data sangat krusial untuk jejak audit (*audit trail*) dan penelusuran status awal persetujuan (*pending approval*).
* **Contoh Potongan Log:**
  ```json
  {
    "message": "Laporan barang baru berhasil disimpan ke basis data",
    "context": {
      "event": "item.created",
      "actor_id": 2,
      "actor_role": "user",
      "action": "create_lost_item",
      "item_id": 14,
      "item_name": "KTM Mahasiswa UGM",
      "category": "Elektronik & Dokumen",
      "status": "pending",
      "has_image": true,
      "reason": "user_submitted_report"
    },
    "level": "INFO"
  }
  ```

#### B. Level `WARNING` — Alur Otentikasi Gagal / Anomali Logika Bisnis
* **Lokasi Berkas:** `backend/app/Http/Controllers/Api/AuthController.php` (method `login()`) dan `ItemReportController.php` (method `store()`)
* **Alasan Pemilihan Alur:** Percobaan login yang gagal mengindikasikan potensi kesalahan ketik pengguna, percobaan serangan *brute force*, atau upaya akses tanpa izin.
* **Proteksi Privasi:** Nilai *password* atau data rahasia **sama sekali tidak dicatat**. Hanya alamat IP dan alamat email sasaran yang dicatat untuk kebutuhan analisis keamanan.
* **Contoh Potongan Log:**
  ```json
  {
    "message": "Percobaan otentikasi login pengguna gagal: kredensial tidak cocok",
    "context": {
      "event": "auth.login_failed",
      "actor_ip": "172.22.0.1",
      "attempted_email": "intruder@contoh.test",
      "action": "user_login",
      "reason": "invalid_credentials"
    },
    "level": "WARNING"
  }
  ```

#### C. Level `ERROR` — Alur Kegagalan Layanan Basis Data / Dependensi
* **Lokasi Berkas:** `backend/app/Http/Controllers/HealthController.php` (method `check()`)
* **Alasan Pemilihan Alur:** Kegagalan saat memeriksa konektivitas basis data merupakan gangguan fatal pada infrastruktur aplikasi yang memerlukan perhatian segera oleh tim operasi (*DevOps / SRE*).
* **Contoh Potongan Log:**
  ```json
  {
    "message": "Pemeriksaan kesehatan sistem gagal: layanan dependensi tidak sehat",
    "context": {
      "event": "system.health_check_failed",
      "actor": "health_probe",
      "action": "health_check",
      "services": {
        "database": "unhealthy",
        "cache": "healthy"
      },
      "errors": {
        "database": "SQLSTATE[HY000] [2002] Connection refused"
      },
      "reason": "dependency_service_unavailable"
    },
    "level": "ERROR"
  }
  ```

---

### 3. Endpoint `/health` (Deep Check DB & Cache)
Endpoint `GET /health` dan `GET /api/health` melakukan verifikasi aktif (*active probing*) terhadap:
1. **Basis Data MySQL:** Membuka koneksi riil via `DB::connection()->getPdo()` dan mengeksekusi `SELECT 1`.
2. **Layanan Cache:** Menjalankan operasi tulis & baca (`Cache::put()` $\rightarrow$ `Cache::get()` $\rightarrow$ `Cache::forget()`).

#### Respons Saat Kondisi Sehat (HTTP 200 OK):
```json
{
  "status": "healthy",
  "timestamp": "2026-11-05T04:25:00+00:00",
  "services": {
    "database": "healthy",
    "cache": "healthy"
  }
}
```

#### Respons Saat Kondisi Sakit / DB Down (HTTP 503 Service Unavailable):
```json
{
  "status": "unhealthy",
  "timestamp": "2026-11-05T04:26:15+00:00",
  "services": {
    "database": "unhealthy",
    "cache": "healthy"
  },
  "errors": {
    "database": "SQLSTATE[HY000] [2002] Connection refused"
  }
}
```

---

### 4. Konfigurasi Healthcheck di `compose.yaml`

Pada berkas `compose.yaml` (dan `docker-compose.yml`), layanan `backend` dikonfigurasikan dengan probe kesehatan aktif:
```yaml
  backend:
    # ...
    healthcheck:
      test: ["CMD-SHELL", "curl -f http://localhost:80/health || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 3
      start_period: 20s
```

* **`test`**: Mengakses endpoint `/health` melalui reverse proxy port 80 lokal container. Mengembalikan exit code 0 saat menerima HTTP 200, atau exit code 1 saat menerima HTTP 503.
* **`interval: 10s`**: Probe kesehatan diuji setiap 10 detik.
* **`timeout: 5s`**: Batas waktu toleransi tunggu respon probe maksimal 5 detik.
* **`retries: 3`**: Status container baru dinyatakan `unhealthy` setelah 3 kali kegagalan berturut-turut.
* **`start_period: 20s`**: Masa toleransi inisialisasi awal container (booting FrankenPHP dan migrasi database) sebelum status kegagalan dihitung.

---

### 5. Skenario Pengujian Dua Arah (Sehat $\rightarrow$ Sakit $\rightarrow$ Pulih)

Panduan langkah uji coba simulasi mandiri di terminal:

```bash
# 1. Jalankan seluruh kontainer
docker compose up -d

# 2. Periksa status awal (Semua Healthy)
docker compose ps
# Output: database (healthy), backend (healthy), frontend (running)

# 3. Uji endpoint /health saat sehat
curl -i http://localhost:8000/health
# Output: HTTP/1.1 200 OK, {"status": "healthy", ...}

# 4. Matikan layanan database dengan sengaja
docker compose stop database

# 5. Tunggu 15-30 detik (interval * retries), lalu periksa status berubah jadi UNHEALTHY
docker compose ps
# Output: database (Exited), backend (unhealthy)

# 6. Uji endpoint /health saat sakit
curl -i http://localhost:8000/health
# Output: HTTP/1.1 503 Service Unavailable, {"status": "unhealthy", "errors": {"database": "..."}}

# 7. Periksa log backend untuk melihat pencatatan log ERROR
docker compose logs --tail=50 backend
# Output: Log ERROR terstruktur mencatat dependency_service_unavailable

# 8. Nyalakan kembali database
docker compose start database

# 9. Tunggu hingga status pulih kembali menjadi HEALTHY
docker compose ps
# Output: database (healthy), backend (healthy)
```

---

### 6. Pembatasan Ukuran Log Container (`max-size` & `max-file`)

Mencegah penumpukan log tak terbatas yang berisiko menghabiskan kapasitas disk host:
```yaml
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
```
Konfigurasi ini memastikan bahwa setiap kontainer membatasi ukuran satu file log maksimal 10 Megabyte dan mempertahankan maksimal 3 file arsip rotasi (*total kapasitas disk untuk log kontainer maksimal 30 MB per layanan*).

---

## 📸 Matriks Bukti Screenshot Laporan (SS-P10-01 s.d. SS-P10-12)

| Kode SS | Nama Bukti & Skenario | Perintah / Lokasi Pengambilan | Elemen Visual yang Wajib Terlihat |
|:---:|:---|:---|:---|
| **SS-P10-01** | **Stdout Logging Aktif** | Terminal: `docker compose logs backend` | Baris log Laravel tampil langsung di terminal kontainer. |
| **SS-P10-02** | **Log Terstruktur `INFO`** | Terminal: `docker compose logs backend` | Log JSON penyimpanan data barang (aktor, item_id, status pending). |
| **SS-P10-03** | **Log Terstruktur `WARNING`** | Terminal: `docker compose logs backend` | Log JSON otentikasi login gagal tanpa kebocoran data sensitif. |
| **SS-P10-04** | **Log Terstruktur `ERROR`** | Terminal: `docker compose logs backend` | Log JSON kegagalan koneksi database dari health probe. |
| **SS-P10-05** | **Endpoint `/health` Sehat (200 OK)** | Browser / curl / Postman: `GET /health` | Status HTTP 200 dan JSON `status: healthy` (database: up, cache: up). |
| **SS-P10-06** | **Status Compose Saat Sehat** | Terminal: `docker compose ps` | Kolom `STATUS` menampilkan `(healthy)` untuk backend dan database. |
| **SS-P10-07** | **Simulasi Matikan DB** | Terminal: `docker compose stop database` | Perintah stop database berhasil dieksekusi. |
| **SS-P10-08** | **Status Compose Berubah `unhealthy`** | Terminal: `docker compose ps` | Kolom `STATUS` service `backend` berubah menjadi `(unhealthy)`. |
| **SS-P10-09** | **Endpoint `/health` Sakit (503)** | Browser / curl / Postman: `GET /health` | Status HTTP 503 dan rincian komponen bermasalah (`database: unhealthy`). |
| **SS-P10-10** | **Log Penyebab Unhealthy** | Terminal: `docker compose logs backend` | Log error terstruktur mencatat koneksi MySQL terputus. |
| **SS-P10-11** | **Pemulihan Status Menjadi `healthy`** | Terminal: `docker compose start database` $\rightarrow$ `docker compose ps` | Status backend pulih kembali menjadi `(healthy)`. |
| **SS-P10-12** | **Konfigurasi Logging Compose** | Text Editor: `compose.yaml` | Blok konfigurasi `logging:` (`driver: "json-file"`, `max-size: "10m"`, `max-file: "3"`). |
