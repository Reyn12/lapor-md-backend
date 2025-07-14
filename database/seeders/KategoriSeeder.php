<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoris = [
            [
                'nama_kategori' => 'Infrastruktur',
                'deskripsi' => 'Pengaduan terkait jalan rusak, jembatan, lampu jalan, drainase dan infrastruktur publik lainnya'
            ],
            [
                'nama_kategori' => 'Kebersihan',
                'deskripsi' => 'Pengaduan terkait sampah, kebersihan lingkungan, dan pengelolaan limbah'
            ],
            [
                'nama_kategori' => 'Keamanan',
                'deskripsi' => 'Pengaduan terkait keamanan lingkungan, pencurian, keributan, dan gangguan ketertiban'
            ],
            [
                'nama_kategori' => 'Pelayanan Publik',
                'deskripsi' => 'Pengaduan terkait pelayanan administrasi, birokrasi, dan layanan pemerintahan'
            ],
            [
                'nama_kategori' => 'Kesehatan',
                'deskripsi' => 'Pengaduan terkait fasilitas kesehatan, sanitasi, dan lingkungan tidak sehat'
            ],
            [
                'nama_kategori' => 'Pendidikan',
                'deskripsi' => 'Pengaduan terkait fasilitas pendidikan dan layanan pendidikan publik'
            ],
            [
                'nama_kategori' => 'Lingkungan',
                'deskripsi' => 'Pengaduan terkait pencemaran lingkungan, kerusakan alam, dan konservasi'
            ],
            [
                'nama_kategori' => 'Sosial',
                'deskripsi' => 'Pengaduan terkait masalah sosial kemasyarakatan dan kesejahteraan'
            ],
            [
                'nama_kategori' => 'Ekonomi',
                'deskripsi' => 'Pengaduan terkait perdagangan, pasar, dan kegiatan ekonomi'
            ],
            [
                'nama_kategori' => 'Lainnya',
                'deskripsi' => 'Pengaduan yang tidak termasuk dalam kategori di atas'
            ]
        ];

        foreach ($kategoris as $kategori) {
            Kategori::create($kategori);
        }

        echo "✅ " . count($kategoris) . " kategori berhasil dibuat!\n";
    }
}
