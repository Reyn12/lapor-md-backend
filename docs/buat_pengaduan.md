# API Buat Pengaduan

## Endpoint
```
POST /api/warga/pengaduan
```

## Headers
```
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
Accept: application/json
```

## Request Body (form-data)
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `kategori_id` | integer | ✅ | ID kategori (1-10) |
| `judul` | string | ✅ | Judul pengaduan (max 255 char) |
| `deskripsi` | text | ✅ | Deskripsi lengkap pengaduan |
| `lokasi` | string | ✅ | Lokasi kejadian (max 255 char) |
| `foto_pengaduan` | file | ❌ | Gambar JPG/PNG/JPEG (max 5MB) |

## Response

### ✅ Success (201)
```json
{
    "success": true,
    "message": "Pengaduan berhasil dibuat",
    "data": {
        "pengaduan": {
            "id": 6,
            "nomor_pengaduan": "PGD-20250716-0006",
            "judul": "Sampah Berserakan di Taman Kota",
            "status": "menunggu",
            "tanggal_pengaduan": "2025-07-16 10:48:04"
        }
    }
}
```

### ❌ Validation Error (422)
```json
{
    "success": false,
    "message": "Data tidak valid",
    "errors": {
        "kategori_id": ["Kategori harus dipilih"],
        "judul": ["Judul pengaduan harus diisi"],
        "deskripsi": ["Deskripsi pengaduan harus diisi"],
        "lokasi": ["Lokasi harus diisi"]
    }
}
```

### ❌ Unauthorized (401)
```json
{
    "success": false,
    "message": "Token tidak valid atau sudah expired"
}
```

### ❌ Forbidden (403)
```json
{
    "success": false,
    "message": "Akses ditolak. Hanya warga yang dapat mengakses endpoint ini"
}
```

### ❌ Server Error (500)
```json
{
    "success": false,
    "message": "Gagal membuat pengaduan: {error_message}"
}
```

## Kategori ID
| ID | Nama Kategori |
|----|---------------|
| 1 | Infrastruktur |
| 2 | Kebersihan |
| 3 | Keamanan |
| 4 | Pelayanan Publik |
| 5 | Kesehatan |
| 6 | Pendidikan |
| 7 | Lingkungan |
| 8 | Sosial |
| 9 | Ekonomi |
| 10 | Lainnya |

## Notes
- Endpoint hanya bisa diakses role **warga**
- Nomor pengaduan auto-generate format: `PGD-YYYYMMDD-XXXX`
- File foto disimpan di `storage/app/public/pengaduan/`
- Sistem akan auto insert ke 3 tabel: `pengaduans`, `status_pengaduans`, `notifikasis`
- Notifikasi otomatis dikirim ke semua pegawai
