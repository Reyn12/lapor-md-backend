Table user {
  id integer [primary key]
  nama varchar(100)
  email varchar(100) [unique]
  email_verified_at timestamp
  password varchar(255)
  no_telepon varchar(20)
  alamat text
  role enum('warga', 'pegawai', 'kepala_kantor')
  foto_profil varchar(255)
  access_token varchar(255)
  refresh_token varchar(255)
  access_expires_at timestamp
  refresh_expires_at timestamp
  remember_token varchar(100)
  created_at timestamp
  updated_at timestamp
}

Table pengaduan {
  id integer [primary key]
  nomor_pengaduan varchar(50) [unique]
  warga_id integer [ref: > user.id]
  pegawai_id integer [ref: > user.id]
  kepala_kantor_id integer [ref: > user.id]
  kategori_id integer [ref: > kategori.id]
  judul varchar(200)
  deskripsi text
  lokasi varchar(255)
  foto_pengaduan varchar(255)
  status enum('menunggu', 'diproses', 'perlu_approval', 'disetujui', 'ditolak', 'selesai')
  tanggal_pengaduan timestamp
  tanggal_proses timestamp
  tanggal_selesai timestamp
  catatan_pegawai text
  catatan_kepala_kantor text
  created_at timestamp
  updated_at timestamp
}

Table kategori {
  id integer [primary key]
  nama_kategori varchar(100)
  deskripsi text
  created_at timestamp
  updated_at timestamp
}

Table status_pengaduan {
  id integer [primary key]
  pengaduan_id integer [ref: > pengaduan.id]
  status varchar(50)
  keterangan text
  created_by integer [ref: > user.id]
  created_at timestamp
}

Table notifikasi {
  id integer [primary key]
  pengguna_id integer [ref: > user.id]
  pengaduan_id integer [ref: > pengaduan.id]
  judul varchar(200)
  pesan text
  dibaca boolean [default: false]
  created_at timestamp
}

Table laporan {
  id integer [primary key]
  dibuat_oleh integer [ref: > user.id]
  jenis_laporan enum('harian', 'mingguan', 'bulanan', 'tahunan')
  tanggal_mulai date
  tanggal_selesai date
  total_pengaduan integer
  pengaduan_selesai integer
  pengaduan_proses integer
  file_laporan varchar(255)
  created_at timestamp
}