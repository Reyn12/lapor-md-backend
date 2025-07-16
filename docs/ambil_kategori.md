# API Ambil Kategori

## Endpoint
```
GET /api/kategori
```

## Headers
```
Authorization: Bearer {access_token}
Accept: application/json
```

## Description
Endpoint untuk mengambil semua kategori pengaduan yang tersedia. Digunakan untuk menampilkan dropdown/select option di form buat pengaduan atau filter pengaduan.

## Response

### ✅ Success (200)
```json
{
    "success": true,
    "message": "Data kategori berhasil diambil",
    "data": {
        "kategoris": [
            {
                "id": 1,
                "nama_kategori": "Infrastruktur",
                "deskripsi": "Pengaduan terkait jalan rusak, jembatan, lampu jalan, drainase dan infrastruktur publik lainnya"
            },
            {
                "id": 2,
                "nama_kategori": "Kebersihan",
                "deskripsi": "Pengaduan terkait sampah, kebersihan lingkungan, dan pengelolaan limbah"
            },
            {
                "id": 3,
                "nama_kategori": "Keamanan",
                "deskripsi": "Pengaduan terkait keamanan lingkungan, pencurian, keributan, dan gangguan ketertiban"
            },
            {
                "id": 4,
                "nama_kategori": "Pelayanan Publik",
                "deskripsi": "Pengaduan terkait pelayanan administrasi, birokrasi, dan layanan pemerintahan"
            },
            {
                "id": 5,
                "nama_kategori": "Kesehatan",
                "deskripsi": "Pengaduan terkait fasilitas kesehatan, sanitasi, dan lingkungan tidak sehat"
            },
            {
                "id": 6,
                "nama_kategori": "Pendidikan",
                "deskripsi": "Pengaduan terkait fasilitas pendidikan dan layanan pendidikan publik"
            },
            {
                "id": 7,
                "nama_kategori": "Lingkungan",
                "deskripsi": "Pengaduan terkait pencemaran lingkungan, kerusakan alam, dan konservasi"
            },
            {
                "id": 8,
                "nama_kategori": "Sosial",
                "deskripsi": "Pengaduan terkait masalah sosial kemasyarakatan dan kesejahteraan"
            },
            {
                "id": 9,
                "nama_kategori": "Ekonomi",
                "deskripsi": "Pengaduan terkait perdagangan, pasar, dan kegiatan ekonomi"
            },
            {
                "id": 10,
                "nama_kategori": "Lainnya",
                "deskripsi": "Pengaduan yang tidak termasuk dalam kategori di atas"
            }
        ],
        "total": 10
    }
}
```

### ❌ Unauthorized (401)
```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

### ❌ Server Error (500)
```json
{
    "success": false,
    "message": "Gagal mengambil data kategori",
    "error": "Database connection failed"
}
```

## Usage Example

### Request
```bash
curl -X GET http://localhost:8000/api/kategori \
  -H "Authorization: Bearer your_access_token_here" \
  -H "Accept: application/json"
```

### JavaScript Fetch
```javascript
const response = await fetch('http://localhost:8000/api/kategori', {
    method: 'GET',
    headers: {
        'Authorization': 'Bearer ' + accessToken,
        'Accept': 'application/json'
    }
});

const data = await response.json();
console.log(data.data.kategoris);
```

### Flutter/Dart
```dart
final response = await http.get(
    Uri.parse('http://localhost:8000/api/kategori'),
    headers: {
        'Authorization': 'Bearer $accessToken',
        'Accept': 'application/json',
    },
);

if (response.statusCode == 200) {
    final data = json.decode(response.body);
    final List kategoris = data['data']['kategoris'];
}
```

## Data Usage
Data kategori biasanya digunakan untuk:

1. **Dropdown Form Pengaduan**
   ```javascript
   kategoris.map(kategori => ({
       value: kategori.id,
       label: kategori.nama_kategori
   }))
   ```

2. **Filter Pengaduan**
   ```javascript
   // Tambah "Semua Kategori" di awal
   const filterOptions = [
       { value: '', label: 'Semua Kategori' },
       ...kategoris.map(k => ({ value: k.id, label: k.nama_kategori }))
   ];
   ```

3. **Display dengan Tooltip**
   ```html
   <select title={kategori.deskripsi}>
       <option value={kategori.id}>{kategori.nama_kategori}</option>
   </select>
   ```

## Notes
- Endpoint bisa diakses oleh semua role yang sudah login (**warga**, **pegawai**, **kepala_kantor**)
- Data kategori diurutkan berdasarkan `id` (1, 2, 3, dst)
- Field `deskripsi` bisa digunakan untuk tooltip atau help text
- Total 10 kategori tersedia (fix data dari seeder)
- Response format consistent dengan endpoint lain di aplikasi

## Testing di Postman
```
Method: GET
URL: http://localhost:8000/api/kategori
Headers:
- Authorization: Bearer {your_access_token}
- Accept: application/json

Expected: 200 OK dengan list 10 kategori
```
