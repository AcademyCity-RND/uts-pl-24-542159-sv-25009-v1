# JejakBarang — Design Guideline UI/UX

Oct 21, 2026 · @Yuki

> Dokumen ini menjadi acuan visual, UX, aksesibilitas, dan integrasi
> frontend untuk [PRD.md](./PRD.md). Aturan bisnis, status, endpoint, dan
> payload harus tetap sesuai dengan implementasi backend yang dirujuk PRD.
> Jika terjadi perbedaan, selaraskan kedua dokumen sebelum implementasi
> frontend dilanjutkan.

## Tujuan dan prinsip desain

Antarmuka JejakBarang dirancang agar pengunjung kampus bisa mencari atau melaporkan barang dalam kurang dari satu menit, di ponsel maupun desktop, dalam mode terang maupun gelap. Arah visualnya bersih, lega, dan bernuansa teal.

1. **Barang dulu.** Foto, nama, lokasi, dan tipe (Hilang atau Ditemukan) adalah elemen paling menonjol di setiap kartu.
2. **Satu aksi utama per layar.** Warna aksen (amber) hanya dipakai untuk satu tombol utama, misalnya "Laporkan Barang".
3. **Warna harus bermakna.** Warna status, tipe, dan urgensi hanya dipakai untuk maknanya, bukan untuk dekorasi.
4. **Lega dan konsisten.** Whitespace cukup, border tipis, bayangan minimal, radius seragam.
5. **Terbaca di semua mode.** Kontras memenuhi WCAG AA di mode terang dan gelap.

## Design tokens warna

Palet utama adalah Teal dengan aksen Amber, masing-masing punya nilai untuk mode terang dan gelap. Palet ini menggantikan usulan awal berbasis biru `#2563EB` di tab Persiapan Fase 3.

| Token | Light | Dark | Fungsi |
| --- | --- | --- | --- |
| Primary | `#0F766E` | `#2DD4BF` | Logo, link navigasi aktif, chip filter aktif, ring fokus |
| Aksen (CTA) | `#F59E0B` | `#F59E0B` | Satu tombol aksi utama per layar |
| Success | `#15803D` | `#4ADE80` | Badge Ditemukan, status Approved |
| Warning | `#B45309` | `#FBBF24` | Timer di bawah 24 jam, status Pending |
| Info | `#0369A1` | `#38BDF8` | Link sekunder, status Resolve requested |
| Danger | `#B91C1C` | `#F87171` | Badge Hilang, timer di bawah 12 jam, status Rejected, pesan error |
| Background | `#F8FAFC` | `#0B1220` | Latar halaman |
| Card | `#FFFFFF` | `#121A2B` | Kartu, navbar, input, modal |
| Text | `#0F172A` | `#E8EEF7` | Teks utama |
| Muted | `#64748B` | `#94A3B8` | Teks sekunder, status Resolved |

Token turunan dihitung dari token di atas, bukan diberi hex sendiri:

- **Border:** Text 10% di atas Card.
- **Surface lembut** (area gambar, chip netral): Text 5% di atas Card.
- **Tint status** (latar badge dan timer): warna status 12% di atas Card.
- **Teks di atas Primary atau Aksen:** gelap atau terang mengikuti kecerahan latarnya. Di atas Aksen amber selalu `#0F172A`.

Aturan pemakaian:

- Komposisi kira-kira 60% netral (Background, Card), 30% teks dan border, 10% Primary dan Aksen.
- Status ditampilkan sebagai teks berwarna di atas tint lembut dengan warna yang sama, bukan teks putih di atas warna jenuh.
- Warna tidak boleh jadi satu-satunya pembeda: setiap badge dan timer wajib punya teks atau ikon.

## Tipografi

Seluruh antarmuka memakai satu keluarga font, Inter, dengan fallback `ui-sans-serif, system-ui, sans-serif`, dan enam tingkat ukuran.

| Tingkat | Ukuran | Bobot | Line-height | Pemakaian |
| --- | --- | --- | --- | --- |
| H1 | 28px (mobile 24px) | 700 | 1.2 | Judul halaman, letter-spacing -0.03em |
| H2 | 20px | 600 | 1.3 | Judul section |
| Judul kartu | 16px | 600 | 1.3 | Nama barang |
| Body | 14px | 400 | 1.5 | Deskripsi, isi form |
| Meta | 13px | 500 | 1.4 | Lokasi, label, navigasi |
| Caption | 12px | 600 | 1.4 | Badge, timer, chip |

Aturan:

- Maksimal dua bobot berbeda dalam satu kartu.
- Angka pada timer memakai `font-variant-numeric: tabular-nums` agar tidak bergeser saat berubah.
- Teks Muted hanya untuk informasi sekunder, tidak untuk tombol atau link.
- Teks di bawah 12px tidak dipakai.

## Spacing, radius, border, dan elevasi

Semua jarak berkelipatan 4px, dan bentuk elemen memakai tiga nilai radius saja.

| Aspek | Nilai | Catatan |
| --- | --- | --- |
| Skala spacing | 4, 8, 12, 16, 24, 32, 40px | Tidak memakai nilai di luar skala |
| Padding halaman | 28px desktop, 18px mobile | Lebar konten maksimal 1180px |
| Padding kartu | 16px | Gap antar-kartu 16px |
| Jarak antar-section | 32 sampai 40px | Termasuk jarak ke catatan footer |
| Radius | 16px kartu dan modal, 12px tombol dan input, 999px badge dan chip | Timer chip memakai 8px |
| Border | 1px, warna border turunan | Dipakai di kartu, input, navbar |
| Elevasi mode terang | Bayangan tipis `0 1px 2px` di kartu | Modal `0 12px 32px` dengan opasitas rendah |
| Elevasi mode gelap | Border menggantikan bayangan | Kedalaman datang dari Card yang lebih terang dari Background |

## Komponen inti

Komponen berikut dibuat sebagai komponen Vue modular yang hanya membaca token warna, tanpa hex di dalam komponen.

| Komponen | Spesifikasi |
| --- | --- |
| Navbar | Latar Card, border bawah 1px. Logo Primary tebal. Link Muted, link aktif Primary dan bobot 700. |
| Tombol utama (CTA) | Latar Aksen, teks gelap, radius 12px, padding 11px 18px. Maksimal satu per layar. |
| Tombol sekunder | Transparan dengan border 1px dan teks Text. Hover: surface lembut. |
| Tombol berbahaya | Teks Danger dengan border Danger. Selalu lewat modal konfirmasi. |
| Kartu barang | Foto 120px di atas (surface lembut jika belum ada foto), badge tipe di pojok kiri atas foto, judul, lokasi dengan ikon pin, lalu footer berisi timer dan link detail. |
| Badge tipe | Pill 12px: Hilang (Danger di tint Danger), Ditemukan (Success di tint Success). |
| Timer | Chip radius 8px dengan ikon jam. Warna mengikuti aturan di bagian status. |
| Search dan filter | Search bar radius 12px. Chip filter pill: Semua, Hilang, Ditemukan, kategori. Chip aktif memakai latar Primary. |
| Input form | Tinggi 44px, border 1px, radius 12px. Fokus: ring 2px Primary. Error: border dan pesan Danger di bawah field. |
| Modal | Card dengan radius 16px, overlay gelap 50%, tombol tutup, fokus terkunci di dalam modal. |
| Notifikasi aksi | Banner atau alert inline setelah aksi. Warna mengikuti Success, Danger, atau Info. |

## Mapping status UI

Status laporan dan tipe barang adalah dua hal berbeda dan ditampilkan terpisah: tipe (Hilang atau Ditemukan) muncul di kartu publik, sedangkan status muncul di halaman laporan milik pengguna dan panel admin.

| Status API | Label UI | Warna | Tampil di |
| --- | --- | --- | --- |
| pending | Menunggu review | Warning | Detail item dan admin |
| approved | Aktif | Success | Detail item dan admin |
| resolve requested | Menunggu konfirmasi | Info | Detail item dan admin |
| resolved | Selesai | Muted | Detail item dan admin |
| rejected | Ditolak | Danger | Detail item dan admin |

Alur status berjalan Pending, Approved, Resolve requested, lalu Resolved. Dari Pending, laporan juga bisa berakhir di Rejected. Kartu di beranda publik hanya menampilkan laporan Approved, jadi badge status tidak perlu ditampilkan di sana.

Warna timer mengikuti sisa waktu dari `remaining_seconds` pada API publik:

| Sisa waktu | Tampilan |
| --- | --- |
| 24 jam atau lebih | Netral: teks Muted di surface lembut |
| Kurang dari 24 jam | Warning di tint Warning |
| Kurang dari 12 jam | Danger di tint Danger |
| Habis | Teks "Kedaluwarsa" berwarna Muted, kartu tidak lagi tampil di beranda |

## Layout dan responsif

Antarmuka dirancang mobile-first dengan tiga breakpoint, dan kartu barang berpindah dari satu ke tiga kolom.

| Breakpoint | Lebar | Kolom kartu | Perilaku |
| --- | --- | --- | --- |
| Mobile | di bawah 640px | 1 | Padding 18px. Tombol CTA lebar penuh di bawah judul. Chip filter bisa digeser horizontal. |
| Tablet | 640 sampai 1023px | 2 | Search bar dan chip dalam satu baris jika muat. |
| Desktop | 1024px ke atas | 3 | Konten dibatasi 1180px dan diposisikan di tengah. |

Aturan tambahan:

- Area sentuh minimal 44 x 44px untuk tombol, chip, dan link.
- Tabel admin berubah menjadi daftar kartu di mobile, bukan tabel yang digeser ke samping.
- Nama lokasi panjang dipotong satu baris dengan elipsis dan tidak memecah layout footer kartu.
- Gambar kartu memakai rasio tetap dengan `object-fit: cover` agar tinggi kartu seragam.

## Pola UX dan alur utama

Alur dirancang agar pengunjung bisa mencari tanpa login, sedangkan login baru diminta saat melapor atau mengelola laporan.

**Alur pengunjung dan pelapor**

1. Pengunjung membuka beranda, lalu mencari atau menyaring dengan chip Hilang, Ditemukan, atau kategori.
2. Pengunjung membuka kartu untuk melihat detail barang.
3. Pengunjung menekan "Laporkan Barang". Jika belum login, ia diarahkan ke halaman login lalu kembali ke form setelah berhasil.
4. Pelapor mengisi form (tipe, nama barang, lokasi penyimpanan, kontak, deskripsi, foto) dan mengirim.
5. Laporan berstatus Pending sampai admin menyetujui atau menolak. Setelah submit, pelapor diarahkan ke halaman detail item untuk melihat hasil laporan dan statusnya.
6. Setelah barang kembali, pelapor mengajukan penyelesaian. Status berubah ke Menunggu konfirmasi, lalu Selesai setelah dikonfirmasi.

**State yang wajib dirancang**

| State | Perilaku |
| --- | --- |
| Loading | Skeleton kartu dengan bentuk yang sama, bukan spinner di tengah layar. |
| Kosong | Ilustrasi sederhana, satu kalimat penjelasan, dan tombol "Laporkan Barang". |
| Error validasi | Pesan di bawah field dari objek `errors` pada respons API, fokus pindah ke field pertama yang salah. |
| Error 401 | Arahkan ke login dan kembali ke halaman asal setelah berhasil. |
| Error 403 | Halaman "Akses ditolak" dengan tombol kembali ke beranda. |
| Error jaringan atau 5xx | Banner di atas konten dengan tombol "Coba lagi". Data yang sudah tampil tidak dihapus. |
| Sukses | Tampilkan pesan sukses inline, lalu arahkan ke halaman detail item yang baru dibuat. |

**Aturan interaksi**

- Aksi yang tidak bisa dibatalkan (tolak laporan, hapus) meminta konfirmasi sebelum request dikirim. Implementasi saat ini menggunakan konfirmasi browser dengan teks aksi yang spesifik.
- Upload foto menampilkan pratinjau sebelum dikirim dan pesan jelas jika format atau ukuran tidak sesuai.
- Informasi kontak mengikuti aturan privasi dari API publik dan tidak ditampilkan di kartu.
- Panel admin (`/admin`) memakai tampilan terpisah yang hanya muncul untuk role admin, dengan tiga tab status dan aksi setujui, tolak, proses laporan, atau hapus langsung dari daftar.
- Pada layar kecil, tabel admin saat ini tetap berada dalam container dengan scroll horizontal agar seluruh kolom aksi dan data tetap tersedia.

## Kontrak integrasi frontend-backend

Bagian ini adalah kontrak implementasi frontend terhadap API Laravel yang
sudah tersedia. Nama field, nilai enum, dan bentuk payload di bawah ini tidak
boleh diubah oleh frontend secara sepihak. Teks pada field `message` hanya
untuk ditampilkan kepada pengguna, bukan untuk menentukan alur aplikasi.

### Base URL, autentikasi, dan format umum

- Base URL API berasal dari `VITE_API_URL`; frontend tidak boleh hardcode URL
  backend di dalam komponen.
- Asset gambar menggunakan `VITE_STORAGE_URL`. Helper `buildImageUrl(path)`
  menggabungkan base asset URL dengan `image_path`; komponen tidak boleh
  membentuk URL sendiri.
- Request terproteksi mengirim header:
  ```text
  Authorization: Bearer <token>
  Accept: application/json
  ```
- Token hasil login/register disimpan di `localStorage`. Saat aplikasi
  dipulihkan, frontend memanggil `GET /api/user` untuk menyegarkan data user.
- Response sukses menggunakan `message` dan/atau `data`. Tidak ada kontrak
  `success: true`.
- Response validasi menggunakan:
  ```json
  {
    "message": "Pesan error.",
    "errors": {
      "field": ["Detail validasi."]
    }
  }
  ```
- Resource collection menggunakan `data`, `links`, dan `meta` Laravel.
  Parameter `per_page` default `10` dan dibatasi backend pada `1..50`.

### Daftar endpoint frontend

| Method | Endpoint | Auth | Payload/query | Kegunaan |
| --- | --- | --- | --- | --- |
| `POST` | `/api/register` | Guest | JSON `name`, `email`, `password`, `password_confirmation`, `phone_number?` | Registrasi user |
| `POST` | `/api/login` | Guest | JSON `email`, `password` | Login dan memperoleh token |
| `POST` | `/api/logout` | Sanctum | Tidak ada | Mencabut token aktif |
| `GET` | `/api/user` | Sanctum | Tidak ada | Memulihkan profil/session |
| `GET` | `/api/public/items` | Guest | `per_page?` | Daftar item approved yang belum expired |
| `GET` | `/api/items` | Sanctum | `status?`, `mine?`, `page?`, `per_page?` | Daftar item user/admin |
| `GET` | `/api/items/{id}` | Sanctum | Tidak ada | Detail item |
| `POST` | `/api/items` | Sanctum | `multipart/form-data` item + `image?` | Membuat item pending |
| `POST` atau `PUT` | `/api/items/{id}` | Sanctum | `multipart/form-data`, gunakan `_method=PUT` jika upload | Memperbarui item pending/rejected |
| `PATCH` | `/api/items/{id}/status` | Sanctum | JSON `{ "status": "approved\|rejected\|resolved" }` | Transisi status item |
| `DELETE` | `/api/items/{id}` | Sanctum | Tidak ada | Menghapus item |
| `POST` | `/api/items/{id}/repost` | Sanctum | Tidak ada | Repost item expired/resolved |
| `POST` | `/api/items/{id}/report` | Sanctum | JSON `{ "message": "..." }` | Mengirim report pihak ketiga |
| `GET` | `/api/item-reports` | Admin | `status?`, `page?`, `per_page?` | Daftar report |
| `PATCH` | `/api/item-reports/{id}/status` | Admin | JSON `{ "status": "accepted\|rejected" }` | Memproses report |

`GET /api/items` memiliki perilaku role berikut:

- Admin melihat semua item; filter `status` dapat digunakan.
- User tanpa `status` melihat item miliknya sendiri.
- User dengan `status=approved` melihat item approved publik kecuali
  `mine=true`; status lain dibatasi pada item miliknya.

### Payload request dan validasi

#### Registrasi

```json
{
  "name": "Mahasiswa Contoh",
  "email": "mahasiswa@gmail.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone_number": "08123456789"
}
```

`name` maksimal 255 karakter, `email` harus unik, valid, dan menggunakan
domain `@gmail.com`, password minimal 8 karakter serta harus dikonfirmasi,
dan `phone_number` opsional maksimal 20 karakter. Field `role` tidak pernah
dikirim dari frontend.

#### Item baru dan update

Gunakan `multipart/form-data` dengan field berikut:

| Field | Create | Update | Aturan |
| --- | --- | --- | --- |
| `item_name` | Wajib | Opsional | String, maksimal 255 |
| `category` | Wajib | Opsional | Hanya `lost` atau `found` |
| `location` | Wajib | Opsional | String, maksimal 255 |
| `current_storage` | Wajib | Opsional | String, maksimal 255 |
| `contact_info` | Wajib | Opsional | String, maksimal 255 |
| `reporter_contact_info` | Opsional | Opsional | String, maksimal 255 |
| `chronology` | Wajib | Opsional | String, maksimal 5000 |
| `image` | Opsional | Opsional | JPG/JPEG/PNG, maksimal 2 MB |

Guideline tidak menambahkan field `waktu` karena field tersebut belum
tersedia pada schema dan validasi backend. Label UI “deskripsi” dipetakan ke
`chronology`. Update melalui `POST` harus menyertakan `_method=PUT` bila
frontend ingin memakai method spoofing.

#### Report pihak ketiga

```json
{
  "message": "Barang terlihat di Pos Satpam Gedung Utama."
}
```

`message` wajib berupa string 10 sampai 2000 karakter. Owner tidak boleh
melaporkan item miliknya sendiri. Report hanya dapat dibuat untuk item
approved yang masih aktif dan belum memiliki proses resolusi aktif.

### Bentuk resource dan privasi

Resource item memiliki field berikut:

```json
{
  "id": 1,
  "user_id": 1,
  "item_name": "Dompet",
  "category": "lost",
  "location": "Perpustakaan",
  "current_storage": "Pos keamanan",
  "chronology": "Hilang setelah kuliah.",
  "status": "approved",
  "image_path": "lost_items/dompet.jpg",
  "approved_at": "2026-10-21T08:00:00+00:00",
  "resolved_at": null,
  "expires_at": "2026-10-24T08:00:00+00:00",
  "is_expired": false,
  "remaining_seconds": 172800,
  "created_at": "2026-10-21T07:00:00+00:00",
  "updated_at": "2026-10-21T08:00:00+00:00"
}
```

- Public resource tidak pernah mengandung `contact_info` atau
  `reporter_contact_info`.
- Detail terproteksi hanya mengandung dua field kontak tersebut untuk owner
  atau admin; frontend harus menangani field yang tidak ada, bukan `null`,
  sebagai kondisi normal.
- Public card tidak menampilkan status `approved`; card menampilkan tipe
  `lost`/`found`, lokasi, gambar, dan timer.
- `image_path` adalah path relatif, bukan URL penuh. Gunakan satu helper
  `buildImageUrl()` berbasis konfigurasi asset/storage URL; jangan merangkai
  URL di dalam komponen card.
- Backend tidak memiliki field waktu kejadian terpisah. Jangan menampilkan
  waktu kejadian sebagai data API sampai kontrak backend diperbarui.

Resource report memiliki `id`, `lost_item_id`, `reporter_user_id`, `message`,
`status`, `created_at`, `updated_at`, serta relasi opsional `lost_item` dan
`reporter` ketika endpoint memuat relasi tersebut.

### Status transition dan aksi UI

| Aksi UI | Endpoint/payload | Hak akses | Hasil |
| --- | --- | --- | --- |
| Admin menyetujui item | `PATCH /api/items/{id}/status` `{ "status": "approved" }` | Admin | Item aktif |
| Admin menolak item | `PATCH /api/items/{id}/status` `{ "status": "rejected" }` | Admin | Item ditolak |
| Owner menyelesaikan item | `PATCH /api/items/{id}/status` `{ "status": "resolved" }` | Owner | Item selesai |
| Owner menolak resolusi | `PATCH /api/items/{id}/status` `{ "status": "approved" }` | Owner | Kembali aktif |
| Admin menerima report | `PATCH /api/item-reports/{id}/status` `{ "status": "accepted" }` | Admin | Item menjadi `resolve_requested` |
| Admin menolak report | `PATCH /api/item-reports/{id}/status` `{ "status": "rejected" }` | Admin | Report ditolak |

Frontend tidak boleh mengirim `resolve_requested` atau `closed` sebagai status
manual. Aksi hapus dan penolakan selalu memakai modal konfirmasi.

### Pemetaan HTTP error

| HTTP | Perilaku frontend |
| --- | --- |
| `401` | Hapus session lokal, simpan route asal, arahkan ke login |
| `403` | Tampilkan halaman/komponen “Akses ditolak” |
| `404` | Tampilkan “Data tidak ditemukan” |
| `409` | Tampilkan `message` sebagai konflik state; jangan menganggapnya error field |
| `422` | Petakan `errors[field]` ke field form dan fokuskan error pertama |
| `429` | Tampilkan pesan rate limit dan cegah submit berulang sementara |
| `500`, `502`, `503`, network error | Tampilkan banner retry dan pertahankan data yang sudah tampil |

## Aksesibilitas dan dark mode

Target aksesibilitas adalah WCAG 2.1 AA di kedua mode, dan dark mode memakai token terpisah, bukan hasil membalik warna.

**Aksesibilitas**

- Rasio kontras minimal 4,5:1 untuk teks biasa dan 3:1 untuk teks besar dan komponen UI. Setiap pasangan teks dan latar diperiksa dengan WebAIM Contrast Checker sebelum dikunci.
- Fokus keyboard selalu terlihat: ring 2px Primary dengan offset 2px, di kedua mode.
- Status tidak bergantung pada warna saja: badge punya label teks, timer punya ikon jam.
- Foto barang memakai `alt` berisi nama barang. Emoji atau ikon dekoratif diberi `aria-hidden`.
- Animasi dinonaktifkan atau dikurangi saat `prefers-reduced-motion` aktif.

**Dark mode**

- Default mengikuti `prefers-color-scheme`, dengan toggle manual yang tersimpan di `localStorage`. Atribut `data-theme` dipasang sebelum render pertama agar tidak berkedip.
- Card dibuat sedikit lebih terang dari Background (`#121A2B` di atas `#0B1220`). Tidak memakai hitam atau putih murni untuk latar dan teks.
- Warna Primary dan status dinaikkan terangnya di mode gelap (lihat tabel token), karena versi gelapnya tenggelam di latar gelap.
- Foto barang tidak diberi filter di dark mode.

Implementasi di Vue memakai CSS variables sehingga komponen cukup membaca `var(--color-*)`:

```css
:root {
  --color-primary: #0F766E;
  --color-accent: #F59E0B;
  --color-success: #15803D;
  --color-warning: #B45309;
  --color-info: #0369A1;
  --color-danger: #B91C1C;
  --color-bg: #F8FAFC;
  --color-card: #FFFFFF;
  --color-text: #0F172A;
  --color-muted: #64748B;
}

[data-theme="dark"] {
  --color-primary: #2DD4BF;
  --color-accent: #F59E0B;
  --color-success: #4ADE80;
  --color-warning: #FBBF24;
  --color-info: #38BDF8;
  --color-danger: #F87171;
  --color-bg: #0B1220;
  --color-card: #121A2B;
  --color-text: #E8EEF7;
  --color-muted: #94A3B8;
}
```

## Checklist penerimaan desain

Fase 3 dianggap sesuai guideline jika seluruh butir berikut terpenuhi.

- [x] Token warna light dan dark tersedia sebagai CSS variables dan tidak ada hex yang ditulis langsung di komponen.
- [x] Toggle light dan dark berfungsi, preferensinya tersimpan, dan tidak ada kedipan saat halaman dimuat.
- [x] Hanya satu tombol Aksen per layar.
- [x] Kartu barang menampilkan badge tipe, lokasi, dan timer dengan aturan warna yang benar.
- [x] Semua state (loading, kosong, error validasi, 401, 403, jaringan) sudah dirancang dan diuji.
- [x] Tampilan diuji di lebar 375px, 768px, dan 1280px.
- [x] Pasangan teks dan latar lolos kontras AA di kedua mode.
- [x] Seluruh elemen interaktif bisa dioperasikan dengan keyboard dengan fokus terlihat.
- [x] Tampilan untuk bukti SS-04 sampai SS-07 mengikuti guideline ini.
