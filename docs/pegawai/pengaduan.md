# Dokumentasi API - Management Pengaduan Pegawai

## Endpoint: `GET /api/pegawai/pengaduan`

**⚡ SATU ENDPOINT UNTUK SEMUA FITUR**

Endpoint ini menggabungkan **semua fitur dalam 1 hit API**:
- ✅ **Filtering berdasarkan status** (Masuk/Diproses/Semua)
- ✅ **Pencarian** (search di nomor, judul, lokasi, nama warga, kategori)  
- ✅ **Pagination** (page + limit)
- ✅ **Tab counts** (badge angka di UI)

**Keuntungan:**
- Frontend cuma perlu 1 request untuk semua fitur
- Bisa kombinasi filter + search + pagination sekaligus
- Performance lebih baik, response lebih cepat
- API design yang clean dan standard

**Contoh Kombinasi:**
```bash
# Filter + Search + Pagination sekaligus
GET /api/pegawai/pengaduan?status=diproses&search=jalan&page=2&limit=5

# Hanya search
GET /api/pegawai/pengaduan?search=lampu

# Hanya filter
GET /api/pegawai/pengaduan?status=masuk
```

---

Endpoint ini digunakan untuk mengambil daftar pengaduan yang bisa dikelola oleh pegawai. Termasuk pengaduan baru yang masuk dan pengaduan yang sedang ditangani.

### URL
```
GET /api/pegawai/pengaduan
```

### Headers
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `status` | string | No | `masuk` | Filter status pengaduan: `masuk`, `diproses`, `semua` |
| `search` | string | No | - | Pencarian berdasarkan nomor, judul, lokasi, nama warga, kategori |
| `page` | integer | No | `1` | Nomor halaman untuk pagination |
| `limit` | integer | No | `10` | Jumlah data per halaman |
| **`kategori_id`** | integer | No | - | **Filter by kategori ID** |
| **`prioritas`** | string | No | - | **Filter by prioritas: `urgent`, `high`, `medium`, `low`, `semua`** |
| **`tanggal_dari`** | string | No | - | **Filter tanggal mulai (format: Y-m-d, contoh: 2024-01-01)** |
| **`tanggal_sampai`** | string | No | - | **Filter tanggal selesai (format: Y-m-d, contoh: 2024-01-31)** |

#### Filter Advanced Options:

**Prioritas Filter:**
- **`urgent`**: Pengaduan dibuat kurang dari 24 jam
- **`high`**: Pengaduan dibuat 1-3 hari yang lalu
- **`medium`**: Pengaduan dibuat 3 hari - 1 minggu yang lalu  
- **`low`**: Pengaduan dibuat lebih dari 1 minggu
- **`semua`**: Semua prioritas

#### Status Filter Options:
- **`masuk`**: Pengaduan baru dengan status `menunggu` (belum di-assign ke pegawai)
- **`diproses`**: Pengaduan yang sedang ditangani oleh pegawai yang login
- **`semua`**: Kombinasi pengaduan masuk + yang sedang diproses

### Response Success (200 OK)

```json
{
  "success": true,
  "data": {
    "pengaduan": [
      {
        "id": 1,
        "nomor_pengaduan": "#001",
        "judul": "Jalan Rusak di RT 01",
        "deskripsi": "Jalan berlubang besar yang membahayakan pengendara",
        "status": "menunggu",
        "lokasi": "Jl. Merdeka No. 123",
        "foto_pengaduan": "https://storage.com/foto1.jpg",
        "kategori": {
          "id": 1,
          "nama_kategori": "Infrastruktur"
        },
        "warga": {
          "id": 5,
          "nama": "Ahmad Wijaya"
        },
        "is_urgent": true,
        "can_accept": true,
        "can_update_progress": false,
        "can_complete": false,
        "tanggal_pengaduan": "2024-01-15T00:00:00Z",
        "tanggal_proses": null,
        "created_at": "2024-01-15T10:00:00Z",
        "waktu_relatif": "2 jam yang lalu",
        "catatan_pegawai": null
      },
      {
        "id": 2,
        "nomor_pengaduan": "#002",
        "judul": "Lampu Jalan Mati",
        "deskripsi": "Lampu jalan tidak menyala sejak 3 hari lalu",
        "status": "diproses",
        "lokasi": "Jl. Sudirman No. 45",
        "foto_pengaduan": "https://storage.com/foto2.jpg",
        "kategori": {
          "id": 2,
          "nama_kategori": "Fasilitas Umum"
        },
        "warga": {
          "id": 6,
          "nama": "Siti Nurhaliza"
        },
        "is_urgent": false,
        "can_accept": false,
        "can_update_progress": true,
        "can_complete": false,
        "tanggal_pengaduan": "2024-01-14T00:00:00Z",
        "tanggal_proses": "2024-01-14T14:00:00Z",
        "created_at": "2024-01-14T10:00:00Z",
        "waktu_relatif": "1 hari yang lalu",
        "catatan_pegawai": "Sedang melakukan pengecekan lampu"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3,
      "total_items": 25,
      "per_page": 10
    },
    "tab_counts": {
      "masuk": 15,
      "diproses": 10
    },
    "current_filter": {
      "status": "masuk",
      "search": "",
      "kategori_id": null,
      "prioritas": null,
      "tanggal_dari": null,
      "tanggal_sampai": null
    }
  }
}
```

### Response Error (500 Internal Server Error)

```json
{
  "success": false,
  "message": "Gagal mengambil data pengaduan",
  "error": "Error details..."
}
```

### Field Descriptions

#### Pengaduan Object Fields:

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | ID unik pengaduan |
| `nomor_pengaduan` | string | Nomor pengaduan yang auto-generated |
| `judul` | string | Judul pengaduan |
| `deskripsi` | string | Deskripsi detail pengaduan |
| `status` | string | Status pengaduan (`menunggu`, `diproses`, `perlu_approval`, `disetujui`, `ditolak`, `selesai`) |
| `lokasi` | string | Lokasi kejadian |
| `foto_pengaduan` | string | URL foto pengaduan |
| `kategori` | object | Data kategori pengaduan |
| `warga` | object | Data warga yang membuat pengaduan |
| `is_urgent` | boolean | Flag prioritas urgent (kurang dari 24 jam) |
| `can_accept` | boolean | Apakah bisa di-terima oleh pegawai |
| `can_update_progress` | boolean | Apakah bisa update progress |
| `can_complete` | boolean | Apakah bisa diselesaikan |
| `tanggal_pengaduan` | string | Tanggal pengaduan dibuat |
| `tanggal_proses` | string | Tanggal mulai diproses |
| `created_at` | string | Timestamp pembuatan |
| `waktu_relatif` | string | Waktu relatif (misal: "2 jam yang lalu") |
| `catatan_pegawai` | string | Catatan dari pegawai |

#### Action Flags Logic:

- **`can_accept`**: `true` jika status pengaduan `menunggu` (belum di-assign)
- **`can_update_progress`**: `true` jika pegawai yang login adalah yang menangani pengaduan ini dan status `diproses` atau `perlu_approval`
- **`can_complete`**: `true` jika pegawai yang login adalah yang menangani pengaduan ini dan status `disetujui`

#### Tab Counts:

- **`masuk`**: Jumlah pengaduan dengan status `menunggu`
- **`diproses`**: Jumlah pengaduan yang sedang ditangani pegawai yang login

### Contoh Request

#### 1. Ambil pengaduan masuk (default)
```bash
GET /api/pegawai/pengaduan
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### 2. Ambil pengaduan yang sedang diproses
```bash
GET /api/pegawai/pengaduan?status=diproses
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### 3. Search pengaduan dengan pagination
```bash
GET /api/pegawai/pengaduan?search=jalan&page=2&limit=5
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### 4. Ambil semua pengaduan
```bash
GET /api/pegawai/pengaduan?status=semua
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### 5. Filter Advanced dengan kategori dan prioritas
```bash
GET /api/pegawai/pengaduan?kategori_id=1&prioritas=urgent
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### 6. Filter dengan range tanggal
```bash
GET /api/pegawai/pengaduan?tanggal_dari=2024-01-01&tanggal_sampai=2024-01-31
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### 7. Kombinasi semua filter
```bash
GET /api/pegawai/pengaduan?status=masuk&search=jalan&kategori_id=1&prioritas=urgent&tanggal_dari=2024-01-01&page=1&limit=5
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Middleware & Authorization

- **Authentication**: Menggunakan middleware `auth.token`
- **Role**: Menggunakan middleware `role:pegawai`
- **Access**: Hanya user dengan role `pegawai` yang bisa mengakses endpoint ini

### Search Functionality

Search akan mencari berdasarkan:
- Nomor pengaduan (LIKE)
- Judul pengaduan (LIKE)
- Lokasi (LIKE)
- Deskripsi (LIKE)
- Nama warga (relasi)
- Nama kategori (relasi)

### Status Pengaduan Flow

```
menunggu → diproses → perlu_approval → disetujui → selesai
    ↓           ↓            ↓
  ditolak   ditolak     ditolak
```

### Notes

1. **Urgent Flag**: Pengaduan dianggap urgent jika dibuat kurang dari 24 jam
2. **Pagination**: Default limit 10, maksimal bisa disesuaikan
3. **Real-time Count**: Tab counts dihitung real-time setiap request
4. **Authorization**: Token harus valid dan user harus memiliki role pegawai

---

## Endpoint: `POST /api/pegawai/pengaduan/{id}/terima`

Endpoint untuk menerima pengaduan yang berstatus `menunggu`. Setelah diterima, pengaduan akan menjadi tanggung jawab pegawai yang menerima.

### URL
```
POST /api/pegawai/pengaduan/{id}/terima
```

### Headers
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | ID pengaduan yang akan diterima |

### Request Body
Tidak memerlukan request body.

### Response Success (200 OK)

```json
{
  "success": true,
  "message": "Pengaduan berhasil diterima",
  "data": {
    "pengaduan": {
      "id": 1,
      "nomor_pengaduan": "#001",
      "status": "diproses",
      "pegawai_id": 3,
      "tanggal_proses": "2024-01-15T10:30:00Z"
    }
  }
}
```

### Response Error

#### 404 Not Found
```json
{
  "success": false,
  "message": "Pengaduan tidak ditemukan"
}
```

#### 400 Bad Request
```json
{
  "success": false,
  "message": "Pengaduan sudah tidak bisa diterima"
}
```

### Business Logic
1. **Validasi**: Pengaduan harus berstatus `menunggu`
2. **Update**: Set `pegawai_id`, `status` → `diproses`, `tanggal_proses`
3. **Notifikasi**: Kirim notifikasi ke warga bahwa pengaduan diterima
4. **Auto Catatan**: Set catatan default "Pengaduan telah diterima dan sedang diproses"

---

## Endpoint: `POST /api/pegawai/pengaduan/{id}/selesai`

Endpoint untuk menyelesaikan pengaduan yang sedang ditangani. Hanya pegawai yang menangani pengaduan tersebut yang bisa menyelesaikannya.

### URL
```
POST /api/pegawai/pengaduan/{id}/selesai
```

### Headers
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | ID pengaduan yang akan diselesaikan |

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `catatan_penyelesaian` | string | Yes | Catatan hasil penyelesaian pengaduan (max 1000 karakter) |

### Example Request Body

```json
{
  "catatan_penyelesaian": "Jalan telah diperbaiki dengan baik. Koordinasi dengan PLN untuk perbaikan listrik juga sudah selesai. Hasil sudah diperiksa dan layak untuk digunakan kembali."
}
```

### Response Success (200 OK)

```json
{
  "success": true,
  "message": "Pengaduan berhasil diselesaikan",
  "data": {
    "pengaduan": {
      "id": 2,
      "nomor_pengaduan": "#002",
      "status": "selesai",
      "tanggal_selesai": "2024-01-15T16:30:00Z",
      "catatan_pegawai": "Jalan telah diperbaiki dengan baik. Koordinasi dengan PLN untuk perbaikan listrik juga sudah selesai."
    }
  }
}
```

### Response Error

#### 404 Not Found
```json
{
  "success": false,
  "message": "Pengaduan tidak ditemukan"
}
```

#### 403 Forbidden
```json
{
  "success": false,
  "message": "Anda tidak berhak menyelesaikan pengaduan ini"
}
```

#### 400 Bad Request
```json
{
  "success": false,
  "message": "Pengaduan tidak bisa diselesaikan"
}
```

#### 422 Validation Error
```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "catatan_penyelesaian": [
      "The catatan penyelesaian field is required."
    ]
  }
}
```

### Business Logic
1. **Ownership**: Hanya pegawai yang menangani pengaduan yang bisa menyelesaikan
2. **Status Valid**: Pengaduan harus berstatus `diproses`, `perlu_approval`, atau `disetujui`
3. **Update**: Set `status` → `selesai`, `tanggal_selesai`, update `catatan_pegawai`
4. **Notifikasi**: Kirim notifikasi ke warga bahwa pengaduan sudah selesai

---

## Postman Collection Examples

### 1. GET Pengaduan dengan Filter Advanced
```
Method: GET
URL: {{base_url}}/api/pegawai/pengaduan?status=masuk&kategori_id=1&prioritas=urgent&tanggal_dari=2024-01-01&tanggal_sampai=2024-01-31
Headers:
  Authorization: Bearer {{pegawai_token}}
  Content-Type: application/json
```

### 2. POST Terima Pengaduan
```
Method: POST
URL: {{base_url}}/api/pegawai/pengaduan/1/terima
Headers:
  Authorization: Bearer {{pegawai_token}}
  Content-Type: application/json
Body: (empty)
```

### 3. POST Selesaikan Pengaduan
```
Method: POST
URL: {{base_url}}/api/pegawai/pengaduan/2/selesai
Headers:
  Authorization: Bearer {{pegawai_token}}
  Content-Type: application/json
Body (JSON):
{
  "catatan_penyelesaian": "Sudah koordinasi dengan PLN, menunggu jadwal perbaikan"
}
```

### Environment Variables untuk Postman:
```
base_url: http://localhost:8000
pegawai_token: (your actual token from login)
```
