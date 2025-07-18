<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PegawaiController extends Controller
{
    /**
     * Home dashboard untuk pegawai
     */
    public function home(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (pegawai)
            
            // 1. Statistics untuk dashboard pegawai
            $statistics = [
                'pengaduan_masuk' => Pengaduan::where('status', 'menunggu')->count(), // Pengaduan yang belum di-assign
                'sedang_diproses' => Pengaduan::where('pegawai_id', $user->id)
                    ->whereIn('status', ['diproses', 'perlu_approval', 'disetujui'])
                    ->count(),
                'selesai_hari_ini' => Pengaduan::where('pegawai_id', $user->id)
                    ->where('status', 'selesai')
                    ->whereDate('tanggal_selesai', Carbon::today())
                    ->count(),
            ];

            // 2. Pengaduan Prioritas (Urgent/Baru) - yang belum di-assign atau baru masuk
            $pengaduanPrioritas = Pengaduan::where('status', 'menunggu')
                ->with(['kategori', 'warga'])
                ->orderBy('created_at', 'asc') // Yang lama dulu (FIFO)
                ->limit(5)
                ->get()
                ->map(function ($pengaduan) {
                    // Tentukan prioritas berdasarkan kategori atau waktu
                    $isPrioritas = $pengaduan->created_at->diffInHours(now()) < 24; // Kurang dari 24 jam = prioritas
                    
                    return [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'judul' => $pengaduan->judul,
                        'status' => $pengaduan->status,
                        'kategori' => $pengaduan->kategori ? $pengaduan->kategori->nama_kategori : null,
                        'warga_nama' => $pengaduan->warga ? $pengaduan->warga->nama : null,
                        'lokasi' => $pengaduan->lokasi,
                        'is_urgent' => $isPrioritas,
                        'created_at' => $pengaduan->created_at->toISOString(),
                        'waktu_relatif' => $pengaduan->waktu_relatif
                    ];
                });

            // 3. Pengaduan yang sedang ditangani pegawai ini (recent 5)
            $pengaduanSayaTangani = Pengaduan::where('pegawai_id', $user->id)
                ->with(['kategori', 'warga'])
                ->whereIn('status', ['diproses', 'perlu_approval', 'disetujui'])
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($pengaduan) {
                    return [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'judul' => $pengaduan->judul,
                        'status' => $pengaduan->status,
                        'kategori' => $pengaduan->kategori ? $pengaduan->kategori->nama_kategori : null,
                        'warga_nama' => $pengaduan->warga ? $pengaduan->warga->nama : null,
                        'lokasi' => $pengaduan->lokasi,
                        'tanggal_proses' => $pengaduan->tanggal_proses ? $pengaduan->tanggal_proses->toISOString() : null,
                        'updated_at' => $pengaduan->updated_at->toISOString(),
                        'waktu_relatif' => $pengaduan->updated_at->diffForHumans()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'pengaduan_prioritas' => $pengaduanPrioritas,
                    'pengaduan_saya_tangani' => $pengaduanSayaTangani
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dashboard pegawai',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Management pengaduan untuk pegawai
     */
    public function pengaduan(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (pegawai)
            
            // Query parameters untuk pengaduan management
            $status = $request->query('status', 'masuk'); // masuk, diproses, semua
            $search = $request->query('search', ''); // Search term
            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 10);

            // Filter Advanced
            $kategoriId = $request->query('kategori_id'); // Filter by kategori
            $prioritas = $request->query('prioritas'); // urgent, high, medium, low, semua
            $tanggalDari = $request->query('tanggal_dari'); // Format: Y-m-d
            $tanggalSampai = $request->query('tanggal_sampai'); // Format: Y-m-d
            
            // Query untuk pengaduan management
            $pengaduanQuery = Pengaduan::with(['kategori', 'warga']);
            
            // Filter berdasarkan status
            switch ($status) {
                case 'masuk':
                    $pengaduanQuery->where('status', 'menunggu'); // Pengaduan baru yang belum di-assign
                    break;
                case 'diproses':
                    $pengaduanQuery->where('pegawai_id', $user->id)
                        ->whereIn('status', ['diproses', 'perlu_approval', 'disetujui']);
                    break;
                case 'semua':
                    $pengaduanQuery->where(function($query) use ($user) {
                        $query->where('status', 'menunggu')
                              ->orWhere('pegawai_id', $user->id);
                    });
                    break;
            }
            
            // Search functionality
            if (!empty($search)) {
                $pengaduanQuery->where(function($query) use ($search) {
                    $query->where('nomor_pengaduan', 'LIKE', "%{$search}%")
                          ->orWhere('judul', 'LIKE', "%{$search}%")
                          ->orWhere('lokasi', 'LIKE', "%{$search}%")
                          ->orWhere('deskripsi', 'LIKE', "%{$search}%")
                          ->orWhereHas('warga', function($q) use ($search) {
                              $q->where('nama', 'LIKE', "%{$search}%");
                          })
                          ->orWhereHas('kategori', function($q) use ($search) {
                              $q->where('nama_kategori', 'LIKE', "%{$search}%");
                          });
                });
            }
            
            // Filter Advanced (setelah search functionality)
            // Filter by kategori
            if (!empty($kategoriId)) {
                $pengaduanQuery->where('kategori_id', $kategoriId);
            }

            // Filter by prioritas (berdasarkan umur pengaduan)
            if (!empty($prioritas) && $prioritas !== 'semua') {
                switch ($prioritas) {
                    case 'urgent':
                        $pengaduanQuery->where('created_at', '>=', now()->subHours(24));
                        break;
                    case 'high':
                        $pengaduanQuery->whereBetween('created_at', [now()->subDays(3), now()->subHours(24)]);
                        break;
                    case 'medium':
                        $pengaduanQuery->whereBetween('created_at', [now()->subWeek(), now()->subDays(3)]);
                        break;
                    case 'low':
                        $pengaduanQuery->where('created_at', '<', now()->subWeek());
                        break;
                }
            }

            // Filter by tanggal range
            if (!empty($tanggalDari)) {
                $pengaduanQuery->whereDate('created_at', '>=', $tanggalDari);
            }
            if (!empty($tanggalSampai)) {
                $pengaduanQuery->whereDate('created_at', '<=', $tanggalSampai);
            }
            
            // Count untuk pagination
            $totalItems = $pengaduanQuery->count();
            $totalPages = ceil($totalItems / $limit);
            
            // Ambil data dengan pagination
            $pengaduanList = $pengaduanQuery->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($pengaduan) use ($user) {
                    // Tentukan prioritas berdasarkan waktu
                    $isUrgent = $pengaduan->created_at->diffInHours(now()) < 24;
                    
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
                        'warga' => $pengaduan->warga ? [
                            'id' => $pengaduan->warga->id,
                            'nama' => $pengaduan->warga->nama
                        ] : null,
                        'is_urgent' => $isUrgent,
                        'can_accept' => $pengaduan->status === 'menunggu', // Bisa di-terima kalau masih menunggu
                        'can_update_progress' => $pengaduan->pegawai_id === $user->id && in_array($pengaduan->status, ['diproses', 'perlu_approval']),
                        'can_complete' => $pengaduan->pegawai_id === $user->id && $pengaduan->status === 'disetujui',
                        'tanggal_pengaduan' => $pengaduan->tanggal_pengaduan ? $pengaduan->tanggal_pengaduan->toISOString() : $pengaduan->created_at->toISOString(),
                        'tanggal_proses' => $pengaduan->tanggal_proses ? $pengaduan->tanggal_proses->toISOString() : null,
                        'created_at' => $pengaduan->created_at->toISOString(),
                        'waktu_relatif' => $pengaduan->waktu_relatif,
                        'catatan_pegawai' => $pengaduan->catatan_pegawai
                    ];
                });

            // Count untuk tab badges
            $tabCounts = [
                'masuk' => Pengaduan::where('status', 'menunggu')->count(),
                'diproses' => Pengaduan::where('pegawai_id', $user->id)
                    ->whereIn('status', ['diproses', 'perlu_approval', 'disetujui'])
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'pengaduan' => $pengaduanList,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_items' => $totalItems,
                        'per_page' => $limit
                    ],
                    'tab_counts' => $tabCounts,
                    'current_filter' => [
                        'status' => $status,
                        'search' => $search,
                        'kategori_id' => $kategoriId,
                        'prioritas' => $prioritas,
                        'tanggal_dari' => $tanggalDari,
                        'tanggal_sampai' => $tanggalSampai
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengaduan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terima pengaduan oleh pegawai
     */
    public function terimaPengaduan(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (pegawai)
            
            // Cari pengaduan
            $pengaduan = Pengaduan::find($id);
            
            if (!$pengaduan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaduan tidak ditemukan'
                ], 404);
            }
            
            // Validasi status pengaduan
            if ($pengaduan->status !== 'menunggu') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaduan sudah tidak bisa diterima'
                ], 400);
            }
            
            // Update pengaduan
            $pengaduan->update([
                'pegawai_id' => $user->id,
                'status' => 'diproses',
                'tanggal_proses' => now(),
                'catatan_pegawai' => 'Pengaduan telah diterima dan sedang diproses'
            ]);
            
            // Buat notifikasi untuk warga
            Notifikasi::create([
                'pengguna_id' => $pengaduan->warga_id,
                'pengaduan_id' => $pengaduan->id,
                'judul' => 'Pengaduan Diterima',
                'pesan' => "Pengaduan {$pengaduan->nomor_pengaduan} telah diterima dan sedang diproses oleh pegawai.",
                'dibaca' => false
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pengaduan berhasil diterima',
                'data' => [
                    'pengaduan' => [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'status' => $pengaduan->status,
                        'pegawai_id' => $pengaduan->pegawai_id,
                        'tanggal_proses' => $pengaduan->tanggal_proses->toISOString()
                    ]
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerima pengaduan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Selesaikan pengaduan oleh pegawai
     */
    public function selesaikanPengaduan(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (pegawai)
            
            // Validasi input
            $request->validate([
                'catatan_penyelesaian' => 'required|string|max:1000'
            ]);
            
            // Cari pengaduan
            $pengaduan = Pengaduan::find($id);
            
            if (!$pengaduan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaduan tidak ditemukan'
                ], 404);
            }
            
            // Validasi ownership dan status
            if ($pengaduan->pegawai_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak menyelesaikan pengaduan ini'
                ], 403);
            }
            
            if (!in_array($pengaduan->status, ['diproses', 'perlu_approval', 'disetujui'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaduan tidak bisa diselesaikan'
                ], 400);
            }
            
            // Update pengaduan
            $pengaduan->update([
                'status' => 'selesai',
                'tanggal_selesai' => now(),
                'catatan_pegawai' => $request->catatan_penyelesaian
            ]);
            
            // Buat notifikasi untuk warga
            Notifikasi::create([
                'pengguna_id' => $pengaduan->warga_id,
                'pengaduan_id' => $pengaduan->id,
                'judul' => 'Pengaduan Selesai',
                'pesan' => "Pengaduan {$pengaduan->nomor_pengaduan} telah diselesaikan. {$request->catatan_penyelesaian}",
                'dibaca' => false
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pengaduan berhasil diselesaikan',
                'data' => [
                    'pengaduan' => [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'status' => $pengaduan->status,
                        'tanggal_selesai' => $pengaduan->tanggal_selesai->toISOString(),
                        'catatan_pegawai' => $pengaduan->catatan_pegawai
                    ]
                ]
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan pengaduan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 