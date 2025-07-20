<?php

namespace Database\Seeders;

use App\Models\Pengaduan;
use App\Models\StatusPengaduan;
use App\Models\User;
use App\Models\Kategori;
use Illuminate\Database\Seeder;

class PengaduanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users by role
        $warga = User::where('role', 'warga')->first();
        $pegawai = User::where('role', 'pegawai')->first();
        $kepala = User::where('role', 'kepala_kantor')->first();

        // Get categories
        $infrastruktur = Kategori::where('nama_kategori', 'Infrastruktur')->first();
        $kebersihan = Kategori::where('nama_kategori', 'Kebersihan')->first();
        $keamanan = Kategori::where('nama_kategori', 'Keamanan')->first();
        $pelayanan = Kategori::where('nama_kategori', 'Pelayanan Publik')->first();

        $pengaduans = [
            [
                'warga_id' => $warga->id,
                'kategori_id' => $infrastruktur->id,
                'judul' => 'Jalan Rusak di Jl. Merdeka',
                'deskripsi' => 'Jalan di depan rumah nomor 123 Jl. Merdeka mengalami kerusakan parah dengan lubang besar yang membahayakan pengendara motor. Sudah berlangsung selama 2 minggu.',
                'lokasi' => 'Jl. Merdeka No. 123, RT 01/RW 02',
                'status' => 'menunggu',
                'tanggal_pengaduan' => now()->subDays(3),
            ],
            [
                'warga_id' => $warga->id,
                'pegawai_id' => $pegawai->id,
                'kategori_id' => $kebersihan->id,
                'judul' => 'Sampah Menumpuk di TPS',
                'deskripsi' => 'Tempat Pembuangan Sampah (TPS) di wilayah RT 03 sudah penuh dan sampah mulai berserakan ke jalan. Bau tidak sedap mulai mengganggu warga sekitar.',
                'lokasi' => 'TPS RT 03/RW 04, Kelurahan Sukamaju',
                'status' => 'diproses',
                'tanggal_pengaduan' => now()->subDays(5),
                'tanggal_proses' => now()->subDays(2),
                'catatan_pegawai' => 'Sudah dikoordinasikan dengan Dinas Kebersihan untuk pengangkutan sampah tambahan.',
            ],
            [
                'warga_id' => $warga->id,
                'pegawai_id' => $pegawai->id,
                'kepala_kantor_id' => $kepala->id,
                'kategori_id' => $keamanan->id,
                'judul' => 'Lampu Jalan Mati Total',
                'deskripsi' => 'Lampu penerangan jalan di sepanjang Jl. Sudirman mati total sejak 1 minggu yang lalu. Kondisi ini sangat membahayakan keamanan warga saat malam hari.',
                'lokasi' => 'Jl. Sudirman sepanjang 500m dari pertigaan hingga jembatan',
                'status' => 'selesai',
                'tanggal_pengaduan' => now()->subDays(10),
                'tanggal_proses' => now()->subDays(7),
                'tanggal_selesai' => now()->subDays(1),
                'catatan_pegawai' => 'Tim teknisi telah melakukan pengecekan dan perbaikan sistem kelistrikan.',
                'catatan_kepala_kantor' => 'Perbaikan telah selesai dan lampu berfungsi normal. Terima kasih atas laporannya.',
            ],
            [
                'warga_id' => $warga->id,
                'pegawai_id' => $pegawai->id,
                'kategori_id' => $pelayanan->id,
                'judul' => 'Pelayanan KTP Lambat',
                'deskripsi' => 'Proses pembuatan KTP di kantor kelurahan sangat lambat. Sudah 2 minggu mengajukan tapi belum ada kabar. Petugas sering tidak ada di tempat.',
                'lokasi' => 'Kantor Kelurahan Sukamaju',
                'status' => 'perlu_approval',
                'tanggal_pengaduan' => now()->subDays(6),
                'tanggal_proses' => now()->subDays(3),
                'catatan_pegawai' => 'Sudah melakukan evaluasi SOP pelayanan KTP. Memerlukan persetujuan untuk perbaikan sistem.',
            ],
            [
                'warga_id' => $warga->id,
                'kategori_id' => $infrastruktur->id,
                'judul' => 'Drainase Tersumbat',
                'deskripsi' => 'Saluran air di Jl. Gatot Subroto tersumbat sampah sehingga air menggenang saat hujan. Kondisi ini sudah berlangsung 3 hari.',
                'lokasi' => 'Jl. Gatot Subroto depan warung Bu Sari',
                'status' => 'menunggu',
                'tanggal_pengaduan' => now()->subDay(),
            ],
            [
                'warga_id' => $warga->id,
                'pegawai_id' => $pegawai->id,
                'kepala_kantor_id' => $kepala->id,
                'kategori_id' => $kebersihan->id,
                'judul' => 'Perbaikan Taman Kelurahan',
                'deskripsi' => 'Taman di kelurahan perlu diperbaiki. Fasilitas bermain anak rusak dan perlu penggantian. Sudah disetujui untuk segera diselesaikan.',
                'lokasi' => 'Taman Kelurahan Sukamaju',
                'status' => 'disetujui',
                'tanggal_pengaduan' => now()->subDays(4),
                'tanggal_proses' => now()->subDays(2),
                'catatan_pegawai' => 'Proposal perbaikan sudah disetujui kepala kantor. Siap untuk eksekusi.',
                'catatan_kepala_kantor' => 'Disetujui untuk segera dilaksanakan perbaikan.',
            ],
        ];

        foreach ($pengaduans as $data) {
            $pengaduan = Pengaduan::create($data);
            
            // Create status history
            StatusPengaduan::createStatusHistory(
                $pengaduan->id, 
                $pengaduan->status, 
                'Status pengaduan: ' . $pengaduan->status, 
                $data['pegawai_id'] ?? $data['warga_id']
            );
        }

        echo "✅ " . count($pengaduans) . " pengaduan sample berhasil dibuat!\n";
        echo "📊 Status: 2 menunggu, 1 diproses, 1 perlu_approval, 1 selesai\n";
    }
}
