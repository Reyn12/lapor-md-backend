# API Detail Pengaduan Warga

## Endpoint
```
GET /api/warga/pengaduan/{id}
```

## Deskripsi
Endpoint ini digunakan untuk mengambil detail lengkap pengaduan milik warga yang sedang login, termasuk timeline status, catatan pegawai/kepala kantor, dan riwayat perubahan status.

## Authentication
- **Required**: Ya
- **Type**: Bearer Token
- **Header**: `Authorization: Bearer {access_token}`
- **Role**: warga

## Path Parameters

| Parameter | Type | Required | Deskripsi |
|-----------|------|----------|-----------|
| `id` | integer | Yes | ID pengaduan yang ingin dilihat detailnya |

## Request Example

```bash
GET /api/warga/pengaduan/1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

## Response

### Success Response (200 OK)

```json
{
  "success": true,
  "data": {
    "pengaduan": {
      "id": 1,
      "nomor_pengaduan": "PGD-20250714-0001",
      "judul": "Jalan Rusak",
      "deskripsi": "Terdapat lubang besar di tengah jalan yang sangat berbahaya bagi pengendara motor dan mobil. Lubang tersebut berdiameter sekitar 2 meter dengan kedalaman 30cm. Kondisi ini sudah berlangsung selama 2 minggu dan semakin parah karena hujan. Mohon segera diperbaiki karena...",
      "status": "selesai",
      "lokasi": "Jl. Merdeka No. 123, RT 02 RW 03",
      "foto_pengaduan": [
        "https://example.com/storage/pengaduan/foto1.jpg",
        "https://example.com/storage/pengaduan/foto2.jpg"
      ],
      "kategori": {
        "id": 1,
        "nama_kategori": "Infrastruktur"
      },
      "tanggal_pengaduan": "2025-07-14T09:00:00Z",
      "tanggal_proses": "2025-07-14T10:30:00Z",
      "tanggal_selesai": "2025-07-16T16:00:00Z",
      "estimasi_selesai": null,
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
      },
      "catatan_pegawai": "Perbaikan jalan telah dilakukan, lubang sudah ditambal dengan aspal baru. Kualitas perbaikan sangat baik dan tahan lama.",
      "catatan_kepala_kantor": "Approved, hasil kerja memuaskan. Tim telah bekerja dengan profesional dan tepat waktu.",
      "timeline": [
        {
          "status": "menunggu",
          "keterangan": "Pengaduan berhasil dibuat oleh warga",
          "tanggal": "2025-07-14T09:00:00Z",
          "dibuat_oleh": "John Doe"
        },
        {
          "status": "diproses",
          "keterangan": "Diterima dan ditinjau oleh Sari",
          "tanggal": "2025-07-14T10:30:00Z",
          "dibuat_oleh": "Sari"
        },
        {
          "status": "selesai",
          "keterangan": "Perbaikan jalan telah selesai dilakukan",
          "tanggal": "2025-07-16T16:00:00Z",
          "dibuat_oleh": "Tim Lapangan"
        }
      ],
      "foto_hasil": {
        "sebelum": [],
        "sesudah": []
      }
    }
  }
}
```

### Error Responses

#### 404 Not Found
```json
{
  "success": false,
  "message": "Pengaduan tidak ditemukan atau bukan milik anda"
}
```

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
  "message": "Gagal mengambil detail pengaduan",
  "error": "Error message detail"
}
```

## Field Descriptions

### Pengaduan Object
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | ID unik pengaduan |
| `nomor_pengaduan` | string | Nomor pengaduan yang auto-generated |
| `judul` | string | Judul/subjek pengaduan |
| `deskripsi` | string | Deskripsi detail lengkap pengaduan |
| `status` | string | Status pengaduan saat ini |
| `lokasi` | string | Lokasi kejadian |
| `foto_pengaduan` | array | Array URL foto pengaduan |
| `kategori` | object/null | Data kategori pengaduan |
| `tanggal_pengaduan` | string/null | Tanggal pengaduan dibuat (ISO 8601) |
| `tanggal_proses` | string/null | Tanggal mulai diproses (ISO 8601) |
| `tanggal_selesai` | string/null | Tanggal selesai (ISO 8601) |
| `estimasi_selesai` | string/null | Estimasi tanggal selesai (belum implementasi) |
| `warga` | object/null | Data warga yang melaporkan |
| `pegawai` | object/null | Data pegawai yang menangani |
| `kepala_kantor` | object/null | Data kepala kantor yang approve |
| `catatan_pegawai` | string/null | Catatan dari pegawai yang menangani |
| `catatan_kepala_kantor` | string/null | Catatan dari kepala kantor |
| `timeline` | array | Timeline perubahan status pengaduan |
| `foto_hasil` | object | Foto sebelum dan sesudah perbaikan |

### Timeline Object
| Field | Type | Description |
|-------|------|-------------|
| `status` | string | Status pada timeline ini |
| `keterangan` | string/null | Keterangan/deskripsi perubahan |
| `tanggal` | string | Tanggal perubahan status (ISO 8601) |
| `dibuat_oleh` | string | Nama user yang membuat perubahan |

### Foto Hasil Object
| Field | Type | Description |
|-------|------|-------------|
| `sebelum` | array | Array URL foto kondisi sebelum perbaikan |
| `sesudah` | array | Array URL foto kondisi sesudah perbaikan |

## Status Values

| Status | Deskripsi |
|--------|-----------|
| `menunggu` | Pengaduan baru, belum diproses |
| `diproses` | Sedang dalam proses penanganan |
| `perlu_approval` | Menunggu persetujuan kepala kantor |
| `disetujui` | Telah disetujui kepala kantor |
| `ditolak` | Ditolak oleh kepala kantor |
| `selesai` | Pengaduan telah selesai ditangani |

## Security Notes
- Warga hanya bisa mengakses detail pengaduan milik mereka sendiri
- Jika mencoba akses pengaduan milik warga lain, akan mendapat error 404
- Authorization token wajib valid dan belum expired

## Implementation Notes
- Field `estimasi_selesai` belum diimplementasi (nilai null)
- Field `foto_hasil` (sebelum/sesudah) belum diimplementasi (array kosong)
- Field `foto_pengaduan` mendukung format:
  - Single URL string: diconvert ke array dengan 1 element
  - JSON array string: diparsing menjadi array
- Timeline diurutkan berdasarkan tanggal (asc), dari yang lama ke yang baru
- Jika data relasi (pegawai, kepala_kantor) tidak ada, akan bernilai null 