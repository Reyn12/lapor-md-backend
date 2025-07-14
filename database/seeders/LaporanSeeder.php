<?php

namespace Database\Seeders;

use App\Models\Laporan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LaporanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users who can create reports
        $pegawai = User::where('role', 'pegawai')->first();
        $kepala = User::where('role', 'kepala_kantor')->first();

        $laporans = [
            // Laporan Harian
            [
                'dibuat_oleh' => $pegawai->id,
                'jenis_laporan' => 'harian',
                'tanggal_mulai' => Carbon::yesterday(),
                'tanggal_selesai' => Carbon::yesterday(),
                'total_pengaduan' => 3,
                'pengaduan_selesai' => 1,
                'pengaduan_proses' => 2,
                'file_laporan' => 'laporan_harian_' . Carbon::yesterday()->format('Y-m-d') . '.pdf',
            ],
            [
                'dibuat_oleh' => $pegawai->id,
                'jenis_laporan' => 'harian',
                'tanggal_mulai' => Carbon::today(),
                'tanggal_selesai' => Carbon::today(),
                'total_pengaduan' => 2,
                'pengaduan_selesai' => 0,
                'pengaduan_proses' => 1,
                'file_laporan' => 'laporan_harian_' . Carbon::today()->format('Y-m-d') . '.pdf',
            ],

            // Laporan Mingguan
            [
                'dibuat_oleh' => $kepala->id,
                'jenis_laporan' => 'mingguan',
                'tanggal_mulai' => Carbon::now()->startOfWeek()->subWeek(),
                'tanggal_selesai' => Carbon::now()->endOfWeek()->subWeek(),
                'total_pengaduan' => 15,
                'pengaduan_selesai' => 8,
                'pengaduan_proses' => 5,
                'file_laporan' => 'laporan_mingguan_minggu_' . Carbon::now()->subWeek()->weekOfYear . '.pdf',
            ],
            [
                'dibuat_oleh' => $pegawai->id,
                'jenis_laporan' => 'mingguan',
                'tanggal_mulai' => Carbon::now()->startOfWeek(),
                'tanggal_selesai' => Carbon::now()->endOfWeek(),
                'total_pengaduan' => 12,
                'pengaduan_selesai' => 4,
                'pengaduan_proses' => 6,
                'file_laporan' => 'laporan_mingguan_minggu_' . Carbon::now()->weekOfYear . '.pdf',
            ],

            // Laporan Bulanan
            [
                'dibuat_oleh' => $kepala->id,
                'jenis_laporan' => 'bulanan',
                'tanggal_mulai' => Carbon::now()->startOfMonth()->subMonth(),
                'tanggal_selesai' => Carbon::now()->endOfMonth()->subMonth(),
                'total_pengaduan' => 87,
                'pengaduan_selesai' => 65,
                'pengaduan_proses' => 18,
                'file_laporan' => 'laporan_bulanan_' . Carbon::now()->subMonth()->format('Y-m') . '.pdf',
            ],
            [
                'dibuat_oleh' => $kepala->id,
                'jenis_laporan' => 'bulanan',
                'tanggal_mulai' => Carbon::now()->startOfMonth(),
                'tanggal_selesai' => Carbon::now()->endOfMonth(),
                'total_pengaduan' => 45,
                'pengaduan_selesai' => 22,
                'pengaduan_proses' => 15,
                'file_laporan' => 'laporan_bulanan_' . Carbon::now()->format('Y-m') . '.pdf',
            ],

            // Laporan Tahunan
            [
                'dibuat_oleh' => $kepala->id,
                'jenis_laporan' => 'tahunan',
                'tanggal_mulai' => Carbon::now()->startOfYear()->subYear(),
                'tanggal_selesai' => Carbon::now()->endOfYear()->subYear(),
                'total_pengaduan' => 1247,
                'pengaduan_selesai' => 1156,
                'pengaduan_proses' => 68,
                'file_laporan' => 'laporan_tahunan_' . Carbon::now()->subYear()->year . '.pdf',
            ]
        ];

        foreach ($laporans as $laporan) {
            Laporan::create($laporan);
        }

        echo "✅ " . count($laporans) . " laporan sample berhasil dibuat!\n";
        echo "📊 Terdiri dari laporan harian, mingguan, bulanan, dan tahunan\n";
        echo "📈 Total statistik yang dilacak: pengaduan selesai, proses, dan menunggu\n";
    }
}
