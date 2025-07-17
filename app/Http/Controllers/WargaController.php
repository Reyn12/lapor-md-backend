<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\Notifikasi;
use App\Models\StatusPengaduan;
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

    /**
     * Riwayat pengaduan untuk warga
     */
    public function riwayat(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware
            
            // Query parameters
            $status = $request->query('status', 'semua'); // semua, menunggu, diproses, selesai
            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 10);
            
            // Mulai query pengaduan milik warga ini
            $query = Pengaduan::byWarga($user->id)
                ->with(['kategori', 'warga', 'pegawai', 'kepalaKantor'])
                ->orderBy('created_at', 'desc');
            
            // Filter berdasarkan status
            if ($status !== 'semua') {
                switch ($status) {
                    case 'menunggu':
                        $query->byStatus('menunggu');
                        break;
                    case 'diproses':
                        $query->whereIn('status', ['diproses', 'perlu_approval', 'disetujui']);
                        break;
                    case 'selesai':
                        $query->byStatus('selesai');
                        break;
                }
            }

            // Count untuk pagination dan statistics
            $totalItems = $query->count();
            $totalPages = ceil($totalItems / $limit);
            
            // Pagination
            $pengaduanList = $query->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($pengaduan) {
                    return [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'judul' => $pengaduan->judul,
                        'deskripsi' => $pengaduan->deskripsi,
                        'status' => $pengaduan->status,
                        'lokasi' => $pengaduan->lokasi,
                        'foto_pengaduan' => $pengaduan->foto_pengaduan,
                        'kategori' => $pengaduan->kategori ? [
                            'id' => $pengaduan->kategori->id,
                            'nama_kategori' => $pengaduan->kategori->nama_kategori
                        ] : null,
                        'tanggal_pengaduan' => $pengaduan->tanggal_pengaduan ? $pengaduan->tanggal_pengaduan->toISOString() : null,
                        'tanggal_proses' => $pengaduan->tanggal_proses ? $pengaduan->tanggal_proses->toISOString() : null,
                        'tanggal_selesai' => $pengaduan->tanggal_selesai ? $pengaduan->tanggal_selesai->toISOString() : null,
                        'warga' => $pengaduan->warga ? [
                            'id' => $pengaduan->warga->id,
                            'nama' => $pengaduan->warga->nama
                        ] : null,
                        'pegawai' => $pengaduan->pegawai ? [
                            'id' => $pengaduan->pegawai->id,
                            'nama' => $pengaduan->pegawai->nama
                        ] : null,
                        'kepala_kantor' => $pengaduan->kepalaKantor ? [
                            'id' => $pengaduan->kepalaKantor->id,
                            'nama' => $pengaduan->kepalaKantor->nama
                        ] : null,
                    ];
                });

            // Siapkan response data
            $responseData = [
                'pengaduan' => $pengaduanList,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalItems,
                    'per_page' => $limit
                ]
            ];

            // Tambah statistics jika status = 'semua'
            if ($status === 'semua') {
                $totalPengaduan = Pengaduan::byWarga($user->id)->count();
                $selesai = Pengaduan::byWarga($user->id)->byStatus('selesai')->count();
                
                $responseData['statistics'] = [
                    'total_pengaduan' => $totalPengaduan,
                    'selesai' => $selesai
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $responseData
            ], 200);

                 } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat pengaduan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail pengaduan untuk warga
     */
    public function detailPengaduan(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware
            
            // Ambil pengaduan dengan validasi ownership
            $pengaduan = Pengaduan::with(['kategori', 'warga', 'pegawai', 'kepalaKantor'])
                ->where('id', $id)
                ->where('warga_id', $user->id) // Pastikan pengaduan milik warga ini
                ->first();

            if (!$pengaduan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaduan tidak ditemukan atau bukan milik anda'
                ], 404);
            }

            // Ambil timeline status dari StatusPengaduan
            $timeline = StatusPengaduan::with('createdBy')
                ->byPengaduan($id)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($status) {
                    return [
                        'status' => $status->status,
                        'keterangan' => $status->keterangan,
                        'tanggal' => $status->created_at ? $status->created_at->toISOString() : null,
                        'dibuat_oleh' => $status->createdBy ? $status->createdBy->nama : 'System'
                    ];
                });

            // Format foto pengaduan sebagai array (asumsi JSON string atau single URL)
            $fotoPengaduan = [];
            if ($pengaduan->foto_pengaduan) {
                // Coba parse sebagai JSON array, kalau gagal jadikan array single
                $decoded = json_decode($pengaduan->foto_pengaduan, true);
                if (is_array($decoded)) {
                    $fotoPengaduan = $decoded;
                } else {
                    $fotoPengaduan = [$pengaduan->foto_pengaduan];
                }
            }

            // Format response
            $response = [
                'id' => $pengaduan->id,
                'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                'judul' => $pengaduan->judul,
                'deskripsi' => $pengaduan->deskripsi,
                'status' => $pengaduan->status,
                'lokasi' => $pengaduan->lokasi,
                'foto_pengaduan' => $fotoPengaduan,
                'kategori' => $pengaduan->kategori ? [
                    'id' => $pengaduan->kategori->id,
                    'nama_kategori' => $pengaduan->kategori->nama_kategori
                ] : null,
                'tanggal_pengaduan' => $pengaduan->tanggal_pengaduan ? $pengaduan->tanggal_pengaduan->toISOString() : null,
                'tanggal_proses' => $pengaduan->tanggal_proses ? $pengaduan->tanggal_proses->toISOString() : null,
                'tanggal_selesai' => $pengaduan->tanggal_selesai ? $pengaduan->tanggal_selesai->toISOString() : null,
                'estimasi_selesai' => null, // Field ini belum ada di database, bisa ditambah nanti
                'warga' => $pengaduan->warga ? [
                    'id' => $pengaduan->warga->id,
                    'nama' => $pengaduan->warga->nama
                ] : null,
                'pegawai' => $pengaduan->pegawai ? [
                    'id' => $pengaduan->pegawai->id,
                    'nama' => $pengaduan->pegawai->nama
                ] : null,
                'kepala_kantor' => $pengaduan->kepalaKantor ? [
                    'id' => $pengaduan->kepalaKantor->id,
                    'nama' => $pengaduan->kepalaKantor->nama
                ] : null,
                'catatan_pegawai' => $pengaduan->catatan_pegawai,
                'catatan_kepala_kantor' => $pengaduan->catatan_kepala_kantor,
                'timeline' => $timeline,
                'foto_hasil' => [
                    'sebelum' => [], // Field ini belum ada di database, bisa ditambah nanti
                    'sesudah' => []  // Field ini belum ada di database, bisa ditambah nanti
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'pengaduan' => $response
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail pengaduan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 