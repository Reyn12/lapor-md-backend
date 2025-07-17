# API Riwayat Pengaduan Warga

## Endpoint
```
GET /api/warga/riwayat
```

## Deskripsi
Endpoint ini digunakan untuk mengambil riwayat pengaduan milik warga yang sedang login. Mendukung pagination dan filtering berdasarkan status.

## Authentication
- **Required**: Ya
- **Type**: Bearer Token
- **Header**: `Authorization: Bearer {access_token}`
- **Role**: warga

## Query Parameters

| Parameter | Type | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `status` | string | No | "semua" | Filter status pengaduan: `semua`, `menunggu`, `diproses`, `selesai` |
| `page` | integer | No | 1 | Halaman yang ingin diambil |
| `limit` | integer | No | 10 | Jumlah data per halaman |

## Request Example

### 1. Ambil Semua Riwayat (Default)
```bash
GET /api/warga/riwayat
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### 2. Filter Status Selesai dengan Pagination
```bash
GET /api/warga/riwayat?status=selesai&page=1&limit=5
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### 3. Filter Status Menunggu
```bash
GET /api/warga/riwayat?status=menunggu
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### 4. Filter Status Diproses
```bash
GET /api/warga/riwayat?status=diproses&page=2&limit=10
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

## Response

### Success Response (200 OK)

#### Jika status = "semua" (include statistics)
```json
{
  "success": true,
  "data": {
    "pengaduan": [
      {
        "id": 1,
        "nomor_pengaduan": "PGD-20250714-0001",
        "judul": "Jalan Rusak",
        "deskripsi": "Terdapat lubang besar di tengah jalan...",
        "status": "selesai",
        "lokasi": "Jl. Merdeka No. 123, RT 02 RW 03",
        "foto_pengaduan": "https://example.com/storage/pengaduan/foto1.jpg",
        "kategori": {
          "id": 1,
          "nama_kategori": "Infrastruktur"
        },
        "tanggal_pengaduan": "2025-07-14T09:00:00Z",
        "tanggal_proses": "2025-07-14T10:30:00Z",
        "tanggal_selesai": "2025-07-16T16:00:00Z",
        "warga": {
          "id": 1,
          "nama": "John Doe"
        },
        "pegawai": {
          "id": 2,
          "nama": "Sari"
        },
        "kepala_kantor": {
          "id": 3,
          "nama": "Pak Joko"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3,
      "total_items": 25,
      "per_page": 10
    },
    "statistics": {
      "total_pengaduan": 25,
      "selesai": 15
    }
  }
}
```

#### Jika status bukan "semua" (tanpa statistics)
```json
{
  "success": true,
  "data": {
    "pengaduan": [
      {
        "id": 2,
        "nomor_pengaduan": "PGD-20250714-0002",
        "judul": "Lampu Jalan Mati",
        "deskripsi": "Lampu jalan di depan rumah sudah mati 3 hari",
        "status": "menunggu",
        "lokasi": "Jl. Sudirman No. 45",
        "foto_pengaduan": "https://example.com/storage/pengaduan/foto2.jpg",
        "kategori": {
          "id": 2,
          "nama_kategori": "Listrik"
        },
        "tanggal_pengaduan": "2025-07-14T15:30:00Z",
        "tanggal_proses": null,
        "tanggal_selesai": null,
        "warga": {
          "id": 1,
          "nama": "John Doe"
        },
        "pegawai": null,
        "kepala_kantor": null
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 3,
      "per_page": 10
    }
  }
}
```

### Error Responses

#### 401 Unauthorized
```json
{
  "success": false,
  "message": "Token tidak valid atau sudah expired"
}
```

#### 403 Forbidden
```json
{
  "success": false,
  "message": "Akses ditolak. Role tidak sesuai"
}
```

#### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Gagal mengambil riwayat pengaduan",
  "error": "Error message detail"
}
```

## Status Filter Values

| Value | Deskripsi |
|-------|-----------|
| `semua` | Menampilkan semua pengaduan + statistics |
| `menunggu` | Hanya pengaduan dengan status "menunggu" |
| `diproses` | Pengaduan dengan status "diproses", "perlu_approval", "disetujui" |
| `selesai` | Hanya pengaduan dengan status "selesai" |

## Field Descriptions

### Pengaduan Object
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | ID unik pengaduan |
| `nomor_pengaduan` | string | Nomor pengaduan yang auto-generated |
| `judul` | string | Judul/subjek pengaduan |
| `deskripsi` | string | Deskripsi detail pengaduan |
| `status` | string | Status pengaduan saat ini |
| `lokasi` | string | Lokasi kejadian |
| `foto_pengaduan` | string/null | URL foto pengaduan |
| `kategori` | object/null | Data kategori pengaduan |
| `tanggal_pengaduan` | string/null | Tanggal pengaduan dibuat (ISO 8601) |
| `tanggal_proses` | string/null | Tanggal mulai diproses (ISO 8601) |
| `tanggal_selesai` | string/null | Tanggal selesai (ISO 8601) |
| `warga` | object/null | Data warga yang melaporkan |
| `pegawai` | object/null | Data pegawai yang menangani |
| `kepala_kantor` | object/null | Data kepala kantor yang approve |

### Pagination Object
| Field | Type | Description |
|-------|------|-------------|
| `current_page` | integer | Halaman saat ini |
| `total_pages` | integer | Total halaman |
| `total_items` | integer | Total data |
| `per_page` | integer | Data per halaman |

### Statistics Object (hanya muncul jika status = "semua")
| Field | Type | Description |
|-------|------|-------------|
| `total_pengaduan` | integer | Total semua pengaduan warga |
| `selesai` | integer | Total pengaduan yang sudah selesai |

## Notes
- Data yang ditampilkan hanya pengaduan milik warga yang sedang login
- Pengaduan diurutkan berdasarkan tanggal dibuat (terbaru dulu)
- Pagination dimulai dari page 1
- Limit maksimal yang disarankan adalah 50 per halaman
- Field yang bernilai `null` artinya belum ada data (misal: pegawai belum assign, tanggal belum ada, dll)



