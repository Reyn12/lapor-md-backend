<?php

namespace Database\Seeders;

use App\Models\Notifikasi;
use App\Models\User;
use App\Models\Pengaduan;
use Illuminate\Database\Seeder;

class NotifikasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users
        $warga = User::where('role', 'warga')->first();
        $pegawai = User::where('role', 'pegawai')->first();
        $kepala = User::where('role', 'kepala_kantor')->first();

        // Get some pengaduan
        $pengaduans = Pengaduan::limit(3)->get();

        $notifikasis = [
            // Notifikasi untuk warga
            [
                'pengguna_id' => $warga->id,
                'pengaduan_id' => $pengaduans[0]->id ?? null,
                'judul' => 'Pengaduan Anda Sedang Diproses',
                'pesan' => 'Pengaduan "Jalan Rusak di Jl. Merdeka" telah diterima dan sedang dalam proses penanganan oleh tim kami.',
                'dibaca' => false,
            ],
            [
                'pengguna_id' => $warga->id,
                'pengaduan_id' => $pengaduans[1]->id ?? null,
                'judul' => 'Status Pengaduan Diperbarui',
                'pesan' => 'Pengaduan "Sampah Menumpuk di TPS" status berubah menjadi: selesai. Terima kasih atas laporan Anda.',
                'dibaca' => true,
            ],
            [
                'pengguna_id' => $warga->id,
                'pengaduan_id' => null,
                'judul' => 'Sistem Maintenance',
                'pesan' => 'Sistem akan mengalami maintenance pada hari Minggu pukul 02:00 - 04:00 WIB. Mohon maaf atas ketidaknyamanannya.',
                'dibaca' => false,
            ],

            // Notifikasi untuk pegawai
            [
                'pengguna_id' => $pegawai->id,
                'pengaduan_id' => $pengaduans[0]->id ?? null,
                'judul' => 'Pengaduan Baru Ditugaskan',
                'pesan' => 'Anda telah ditugaskan untuk menangani pengaduan "Jalan Rusak di Jl. Merdeka". Mohon segera ditindaklanjuti.',
                'dibaca' => false,
            ],
            [
                'pengguna_id' => $pegawai->id,
                'pengaduan_id' => $pengaduans[2]->id ?? null,
                'judul' => 'Deadline Pengaduan',
                'pesan' => 'Pengaduan "Lampu Jalan Mati Total" mendekati batas waktu penyelesaian. Mohon segera diselesaikan.',
                'dibaca' => true,
            ],

            // Notifikasi untuk kepala kantor
            [
                'pengguna_id' => $kepala->id,
                'pengaduan_id' => $pengaduans[1]->id ?? null,
                'judul' => 'Persetujuan Diperlukan',
                'pesan' => 'Pengaduan "Sampah Menumpuk di TPS" memerlukan persetujuan Anda untuk proses penyelesaian.',
                'dibaca' => false,
            ],
            [
                'pengguna_id' => $kepala->id,
                'pengaduan_id' => null,
                'judul' => 'Laporan Bulanan Siap',
                'pesan' => 'Laporan bulanan pengaduan bulan ini telah selesai dibuat dan siap untuk direview.',
                'dibaca' => true,
            ],
            [
                'pengguna_id' => $kepala->id,
                'pengaduan_id' => null,
                'judul' => 'Peningkatan Pengaduan',
                'pesan' => 'Terjadi peningkatan 20% pengaduan kategori infrastruktur minggu ini. Perlu perhatian khusus.',
                'dibaca' => false,
            ]
        ];

        foreach ($notifikasis as $notifikasi) {
            Notifikasi::create($notifikasi);
        }

        echo "✅ " . count($notifikasis) . " notifikasi sample berhasil dibuat!\n";
        echo "📧 Terdiri dari notifikasi untuk warga, pegawai, dan kepala kantor\n";
    }
}
