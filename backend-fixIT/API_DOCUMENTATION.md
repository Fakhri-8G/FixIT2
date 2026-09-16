# 📘 Dokumentasi API — FixIT Backend

Base URL (development):
```
http://127.0.0.1:8000/api
```

Semua response mengikuti format standar:
```json
{
  "status": true | false,
  "message": "string",
  "data": { ... } | [ ... ] | null,
  "errors": { ... } | null   // hanya muncul kalau status: false karena validasi
}
```

Semua endpoint (kecuali register & login) butuh header:
```
Authorization: Bearer {token}
Accept: application/json
```

---

## 🔐 Auth

### Register
```
POST /register
```
**Body (JSON):**
```json
{
  "name": "Budi Santoso",
  "email": "budi@fixit.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```
**Response 201:**
```json
{
  "status": true,
  "message": "Registrasi berhasil.",
  "data": {
    "user": { "id": 1, "name": "Budi Santoso", "email": "budi@fixit.com", "role": "user" },
    "token": "1|xxxxxxxxxxxx"
  }
}
```
> Role selalu otomatis `user` saat register. Untuk akun `admin`, dibuat manual lewat seeder/tinker.

---

### Login
```
POST /login
```
**Body:**
```json
{ "email": "budi@fixit.com", "password": "password123" }
```
**Response 200:** sama seperti register (`user` + `token`).
**Response 401:** email/password salah.

---

### Logout
```
POST /logout
```
🔒 Butuh token. Menghapus token yang sedang dipakai.

---

### Profile (data user yang sedang login)
```
GET /me
```
🔒 Butuh token.
**Response 200:**
```json
{ "status": true, "message": "Data profil berhasil diambil.", "data": { "id": 1, "name": "...", "role": "user" } }
```

---

## 👤 Profile

### Lihat Profil
```
GET /me
```
🔒 Butuh token. Mengembalikan data user yang sedang login.

### Update Profil (Nama & Email)
```
PUT /profile
```
🔒 Butuh token.
**Body:**
```json
{ "name": "Budi Santoso", "email": "budi@fixit.com" }
```
**Response 200:** data user ter-update.
**Response 422:** kalau email sudah dipakai user lain, atau field kosong.

### Ganti Password
```
PUT /profile/password
```
🔒 Butuh token.
**Body:**
```json
{
  "current_password": "password_lama",
  "password": "password_baru",
  "password_confirmation": "password_baru"
}
```
**Response 200:** `{ "status": true, "message": "Password berhasil diperbarui.", "data": null }`
**Response 422:** kalau `current_password` salah, atau password baru tidak memenuhi validasi (`min:8`, harus `confirmed`).

### Upload/Ganti Foto Profil
```
POST /profile/avatar
```
🔒 Butuh token. **Body → `multipart/form-data`:**
| Key | Tipe | Wajib |
|---|---|---|
| avatar | file (jpg/jpeg/png, max 2MB) | ✅ |

**Response 200:** data user, field `avatar_url` berisi URL foto baru siap pakai di `<img>`.
> Avatar lama otomatis dihapus dari storage saat upload avatar baru.

### Statistik Ringkas Laporan
```
GET /profile/stats
```
🔒 Butuh token.
- **Role `user`** → statistik laporan miliknya sendiri
- **Role `admin`** → statistik seluruh laporan di sistem

**Response 200:**
```json
{
  "status": true,
  "message": "Statistik berhasil diambil.",
  "data": {
    "total": 4,
    "reported": 1,
    "verified": 1,
    "processing": 1,
    "completed": 1,
    "rejected": 0
  }
}
```

### Logout dari Semua Device
```
POST /logout-all
```
🔒 Butuh token. Menghapus **semua** token milik user (bukan hanya token yang sedang dipakai) — berguna kalau device lain masih login dan user ingin mengamankan akunnya.
**Response 200:** `{ "status": true, "message": "Berhasil logout dari semua perangkat.", "data": null }`

---

## 🏷️ Category

| Method | Endpoint | Akses |
|---|---|---|
| GET | `/categories` | Semua role (login) |
| POST | `/categories` | Admin only |
| GET | `/categories/{id}` | Semua role |
| PUT | `/categories/{id}` | Admin only |
| DELETE | `/categories/{id}` | Admin only |

**Contoh data:**
```json
{ "id": 1, "name": "Elektronik", "created_at": "...", "updated_at": "..." }
```

**Body untuk POST/PUT:**
```json
{ "name": "Elektronik" }
```

---

## 📍 Location

| Method | Endpoint | Akses |
|---|---|---|
| GET | `/locations` | Semua role |
| POST | `/locations` | Admin only |
| GET | `/locations/{id}` | Semua role |
| PUT | `/locations/{id}` | Admin only |
| DELETE | `/locations/{id}` | Admin only |

**Contoh data:**
```json
{ "id": 1, "name": "Kelas XII RPL 1", "description": "Ruang kelas jurusan RPL" }
```

**Body untuk POST/PUT:**
```json
{ "name": "Kelas XII RPL 1", "description": "Ruang kelas jurusan RPL" }
```

---

## 🔧 Report (Laporan Kerusakan)

### Field yang Ada di Object `Report`

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | number | ID laporan |
| `user_id` | number | ID pelapor |
| `category_id` | number | ID kategori |
| `location_id` | number | ID lokasi |
| `title` | string | Judul kerusakan |
| `description` | string | Deskripsi kerusakan |
| `status` | string | `reported` \| `verified` \| `processing` \| `completed` \| `rejected` |
| `user` | object | **Hanya muncul untuk admin** — data pelapor `{id, name, email}` |
| `category` | object | `{id, name}` |
| `location` | object | `{id, name, description}` |
| `images` | array | Daftar foto, lihat struktur di bawah |
| `updates` | array | Riwayat perubahan status, lihat struktur di bawah |

**Struktur tiap item di `images[]`:**
```json
{ "id": 5, "report_id": 1, "image_path": "reports/abc123.jpg", "image_url": "http://127.0.0.1:8000/storage/reports/abc123.jpg" }
```
> Field `image_url` sudah berupa **URL lengkap siap pakai** di tag `<img>` — tidak perlu digabung manual.

**Struktur tiap item di `updates[]`:**
```json
{ "id": 2, "report_id": 1, "admin_id": 1, "status": "verified", "note": "Sudah dicek, kerusakan valid", "admin": { "id": 1, "name": "Admin FixIT" }, "created_at": "..." }
```
> Ini adalah **riwayat/log**, satu laporan bisa punya banyak baris di sini. Catatan terbaru = item terakhir di array (`updates[updates.length - 1]`).

### Daftar Status yang Valid
```
reported    → Laporan baru masuk, belum ditinjau
verified    → Admin sudah memverifikasi laporan valid
processing  → Sedang dalam perbaikan
completed   → Perbaikan selesai
rejected    → Laporan ditolak (tidak valid)
```

---

### List Laporan
```
GET /reports
```
🔒 Butuh token.
- **Role `user`** → hanya menampilkan laporan miliknya sendiri, field `user` tidak disertakan
- **Role `admin`** → menampilkan **semua** laporan, field `user` disertakan

**Query Parameter (opsional):**

| Param | Keterangan |
|---|---|
| `keyword` | Cari di `title`, `description`, nama lokasi, dan nama pelapor (partial match, case-insensitive) |
| `status` | Filter berdasarkan status, harus salah satu dari 5 value valid (`reported`, `verified`, `processing`, `completed`, `rejected`) |
| `page` | Nomor halaman yang ingin diambil (default: 1) |
| `per_page` | Jumlah item per halaman (default: 10) |

**Contoh:**
```
GET /reports?keyword=proyektor&status=reported&page=1&per_page=10
```

**Response 200 (⚠️ Menggunakan Pagination — Struktur Berbeda dari Endpoint Lain):**
```json
{
  "status": true,
  "message": "Daftar laporan berhasil diambil.",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Proyektor Tidak Menyala",
        "description": "...",
        "status": "reported",
        "category": { "id": 1, "name": "Elektronik" },
        "location": { "id": 1, "name": "Kelas XII RPL 1" },
        "images": [ { "id": 1, "image_url": "..." } ]
      }
    ],
    "first_page_url": "http://127.0.0.1:8000/api/reports?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://127.0.0.1:8000/api/reports?page=3",
    "next_page_url": "http://127.0.0.1:8000/api/reports?page=2",
    "prev_page_url": null,
    "path": "http://127.0.0.1:8000/api/reports",
    "per_page": 10,
    "to": 10,
    "total": 25
  }
}
```
> ⚠️ **PENTING:** Array laporan ada di `data.data`, **bukan** `data` langsung — beda dengan endpoint lain di dokumentasi ini yang tidak dipaginasi. Gunakan `data.current_page`, `data.last_page`, `data.total` untuk membangun kontrol navigasi halaman (tombol next/prev, indikator "Halaman X dari Y").

---

### Buat Laporan Baru
```
POST /reports
```
🔒 Butuh token (role: user/admin).
**Body → wajib `multipart/form-data`** (karena upload file):

| Key | Tipe | Wajib | Keterangan |
|---|---|---|---|
| title | text | ✅ | max 255 karakter |
| description | text | ✅ | |
| category_id | text | ✅ | id kategori yang valid |
| location_id | text | ✅ | id lokasi yang valid |
| images[] | file | ✅ | 1–5 file, format jpg/jpeg/png, max 2MB per file |

**Response 201:** object report lengkap dengan `images[]` yang baru dibuat.

---

### Detail Laporan
```
GET /reports/{id}
```
🔒 Butuh token.
- User biasa hanya bisa akses laporan miliknya sendiri (selain itu → 403)
- Admin bisa akses semua

**Response 200:** object report lengkap termasuk `user`, `category`, `location`, `images`, `updates.admin`.

---

### Update Status Laporan (termasuk Catatan)
```
PUT /reports/{id}
```
🔒 **Admin only**.

**Body (JSON):**
```json
{
  "status": "verified",
  "note": "Sudah dicek, kerusakan valid"
}
```
- `status` wajib diisi, harus salah satu dari 5 value yang valid di atas
- `note` opsional (boleh dikosongkan)

**Efek:** field `status` di `reports` ter-update, **dan** otomatis membuat 1 baris baru di riwayat `updates[]`. Tidak ada endpoint terpisah untuk "hanya update catatan" — catatan selalu dikirim bersamaan saat update status.

**Response 200:** object report ter-update beserta `updates` terbaru.

---

### Hapus Laporan
```
DELETE /reports/{id}
```
🔒 Hanya pemilik laporan, **dan** hanya kalau status masih `reported` (belum diverifikasi admin).

---

### Hapus 1 Gambar dari Laporan
```
DELETE /report-images/{image_id}
```
🔒 Hanya pemilik laporan yang bersangkutan.
> Catatan: `{image_id}` adalah ID dari tabel `report_images` (field `id` di dalam array `images[]`), **bukan** ID laporan.

**Response 200:**
```json
{ "status": true, "message": "Gambar berhasil dihapus.", "data": null }
```

---

## 🔑 Ringkasan Role & Akses

| Aksi | User | Admin |
|---|---|---|
| Register/Login | ✅ | ✅ |
| Lihat kategori/lokasi | ✅ | ✅ |
| Tambah/edit/hapus kategori/lokasi | ❌ | ✅ |
| Buat laporan | ✅ | ✅ (jarang dipakai) |
| Lihat laporan sendiri | ✅ | — |
| Lihat semua laporan | ❌ | ✅ |
| Update status laporan | ❌ | ✅ |
| Hapus laporan sendiri (status reported) | ✅ | — |
| Hapus gambar laporan sendiri | ✅ | — |

---

## 🔑 Akun Testing (dari Seeder)

```
Admin
Email: admin@fixit.com
Password: password

User
Email: budi@fixit.com
Password: password

Email: siti@fixit.com
Password: password
```

Jalankan `php artisan migrate:fresh --seed` untuk mendapatkan data ini beserta contoh kategori, lokasi, dan laporan.

---

## ⚠️ Catatan Penting untuk Frontend

1. **Tidak ada field `tingkat_urgensi` / prioritas laporan** di backend. Kalau dibutuhkan, ini fitur baru yang perlu didiskusikan & ditambah migration dulu — jangan diasumsikan ada di response.
2. **Foto selalu array** (`images[]`), sebuah laporan bisa punya lebih dari 1 foto. Jangan asumsikan hanya 1 foto.
3. **Catatan (`note`) adalah bagian dari riwayat status**, bukan field tunggal yang bisa diedit bebas. Ambil dari `updates[]`, dan kirim bersamaan dengan update status.
4. **Endpoint `GET /reports` menggunakan pagination** — array laporan ada di `data.data`, bukan `data` langsung. Endpoint lain (categories, locations, dst) TIDAK dipaginasi dan tetap mengembalikan array langsung di `data`.
4. Field relasi (`category`, `location`, `user`) berupa **object**, bukan string langsung — akses nama lewat `category.name`, `location.name`, `user.name`.
5. Response **tidak dibungkus pagination** — `data` langsung berupa array, tidak perlu akses `data.data.data`.