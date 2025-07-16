<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WargaController extends Controller
{
    /**
     * Home dashboard untuk warga
     */
    public function home(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware
            
            // 1. Statistics pengaduan milik warga ini
            $statistics = [
                'total' => Pengaduan::byWarga($user->id)->count(),
                'menunggu' => Pengaduan::byWarga($user->id)->byStatus('menunggu')->count(),
                'diproses' => Pengaduan::byWarga($user->id)->whereIn('status', ['diproses', 'perlu_approval', 'disetujui'])->count(),
                'selesai' => Pengaduan::byWarga($user->id)->byStatus('selesai')->count(),
            ];

            // 2. Recent pengaduan (5 terbaru)
            $recentPengaduan = Pengaduan::byWarga($user->id)
                ->with(['kategori'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($pengaduan) {
                    return [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'judul' => $pengaduan->judul,
                        'status' => $pengaduan->status,
                        'kategori' => $pengaduan->kategori ? $pengaduan->kategori->nama_kategori : null,
                        'foto_pengaduan' => $pengaduan->foto_pengaduan,
                        'created_at' => $pengaduan->created_at->toISOString(),
                        'waktu_relatif' => $pengaduan->waktu_relatif
                    ];
                });

            // 3. Recent notifikasi (5 terbaru)
            $recentNotifikasi = Notifikasi::byPengguna($user->id)
                ->with(['pengaduan'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($notifikasi) {
                    return [
                        'id' => $notifikasi->id,
                        'judul' => $notifikasi->judul,
                        'pesan' => $notifikasi->pesan,
                        'is_read' => $notifikasi->dibaca,
                        'pengaduan_nomor' => $notifikasi->pengaduan ? $notifikasi->pengaduan->nomor_pengaduan : null,
                        'created_at' => $notifikasi->created_at->toISOString(),
                        'waktu_relatif' => $notifikasi->waktu_relatif
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'recent_pengaduan' => $recentPengaduan,
                    'recent_notifikasi' => $recentNotifikasi
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data home',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 