<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Models\User; // Added missing import for User model

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
                case 'selesai':
                    $pengaduanQuery->where('pegawai_id', $user->id)
                        ->where('status', 'selesai');
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
                    ->count(),
                'selesai' => Pengaduan::where('pegawai_id', $user->id)
                    ->where('status', 'selesai')
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

    /**
     * Detail pengaduan untuk pegawai
     */
    public function detailPengaduan(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (pegawai)
            
            // Cari pengaduan dengan relasi sesuai kebutuhan UI
            $pengaduan = Pengaduan::with([
                'warga:id,nama', // Cuma nama pelapor
                'kategori:id,nama_kategori' // Cuma nama kategori
            ])->find($id);
            
            if (!$pengaduan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaduan tidak ditemukan'
                ], 404);
            }
            
            // Cek apakah pegawai berhak lihat detail ini
            $canView = false;
            if ($pengaduan->status === 'menunggu') {
                $canView = true; // Semua pegawai bisa lihat pengaduan yang belum di-assign
            } elseif ($pengaduan->pegawai_id === $user->id) {
                $canView = true; // Pegawai yang handle bisa lihat
            }
            
            if (!$canView) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak melihat detail pengaduan ini'
                ], 403);
            }
            
            // Tentukan prioritas berdasarkan waktu
            $isUrgent = $pengaduan->created_at->diffInHours(now()) < 24;
            
            // Format response sesuai UI
            $response = [
                'id' => $pengaduan->id,
                'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                'judul' => $pengaduan->judul,
                'deskripsi' => $pengaduan->deskripsi,
                'lokasi' => $pengaduan->lokasi,
                'foto_pengaduan' => $pengaduan->foto_pengaduan,
                'status' => $pengaduan->status,
                'is_urgent' => $isUrgent,
                'pelapor_nama' => $pengaduan->warga ? $pengaduan->warga->nama : null,
                'kategori_nama' => $pengaduan->kategori ? $pengaduan->kategori->nama_kategori : null,
                'tanggal_pengaduan' => $pengaduan->tanggal_pengaduan ? $pengaduan->tanggal_pengaduan->format('Y-m-d') : $pengaduan->created_at->format('Y-m-d'),
                
                // Permissions untuk button actions
                'can_accept' => $pengaduan->status === 'menunggu',
                'can_update_progress' => $pengaduan->pegawai_id === $user->id && in_array($pengaduan->status, ['diproses', 'perlu_approval']),
                'can_complete' => $pengaduan->pegawai_id === $user->id && $pengaduan->status === 'disetujui'
            ];
            
            return response()->json([
                'success' => true,
                'data' => $response
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail pengaduan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Halaman laporan untuk pegawai
     */
    public function laporan(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (pegawai)
            
            // 1. Riwayat laporan yang udah dibuat
            $riwayatLaporan = \App\Models\Laporan::where('dibuat_oleh', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($laporan) {
                    return [
                        'id' => $laporan->id,
                        'jenis_laporan' => ucfirst($laporan->jenis_laporan),
                        'periode' => $laporan->tanggal_mulai->format('Y-m-d') . ' - ' . $laporan->tanggal_selesai->format('Y-m-d'),
                        'tanggal_dibuat' => $laporan->created_at->format('Y-m-d'),
                        'total_pengaduan' => $laporan->total_pengaduan,
                        'pengaduan_selesai' => $laporan->pengaduan_selesai,
                        'pengaduan_proses' => $laporan->pengaduan_proses,
                        'file_laporan' => $laporan->file_laporan,
                        'status' => 'Selesai' // Kalau udah ke-save berarti selesai
                    ];
                });

            // 2. Data untuk preview (statistik hari ini)
            $today = now();
            $previewData = [
                'tanggal' => $today->format('Y-m-d'),
                'pengaduan_masuk' => Pengaduan::whereDate('created_at', $today)->count(),
                'sedang_diproses' => Pengaduan::where('pegawai_id', $user->id)
                    ->whereIn('status', ['diproses', 'perlu_approval', 'disetujui'])
                    ->count(),
                'selesai' => Pengaduan::where('pegawai_id', $user->id)
                    ->where('status', 'selesai')
                    ->whereDate('tanggal_selesai', $today)
                    ->count()
            ];

            // 3. Options untuk filter
            $filterOptions = [
                'periode' => [
                    ['value' => 'harian', 'label' => 'Harian'],
                    ['value' => 'mingguan', 'label' => 'Mingguan'], 
                    ['value' => 'bulanan', 'label' => 'Bulanan']
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'riwayat_laporan' => $riwayatLaporan,
                    'preview_data' => $previewData,
                    'filter_options' => $filterOptions
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data laporan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate laporan baru
     */
    public function generateLaporan(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (pegawai)
            
            // Validasi input
            $request->validate([
                'jenis_laporan' => 'required|in:harian,mingguan,bulanan',
                'tanggal_mulai' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_mulai'
            ]);

            $jenisLaporan = $request->jenis_laporan;
            $tanggalMulai = Carbon::parse($request->tanggal_mulai);
            $tanggalAkhir = Carbon::parse($request->tanggal_akhir);

            // Hitung statistik berdasarkan periode
            $totalPengaduan = Pengaduan::whereBetween('created_at', [$tanggalMulai, $tanggalAkhir])->count();
            
            $pengaduanSelesai = Pengaduan::where('pegawai_id', $user->id)
                ->where('status', 'selesai')
                ->whereBetween('tanggal_selesai', [$tanggalMulai, $tanggalAkhir])
                ->count();
            
            $pengaduanProses = Pengaduan::where('pegawai_id', $user->id)
                ->whereIn('status', ['diproses', 'perlu_approval', 'disetujui'])
                ->whereBetween('tanggal_proses', [$tanggalMulai, $tanggalAkhir])
                ->count();

            // Simpan laporan ke database
            $laporan = \App\Models\Laporan::create([
                'dibuat_oleh' => $user->id,
                'jenis_laporan' => $jenisLaporan,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalAkhir,
                'total_pengaduan' => $totalPengaduan,
                'pengaduan_selesai' => $pengaduanSelesai,
                'pengaduan_proses' => $pengaduanProses,
                'file_laporan' => null // Nanti bisa ditambahin kalau mau generate PDF
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil digenerate',
                'data' => [
                    'laporan' => [
                        'id' => $laporan->id,
                        'jenis_laporan' => ucfirst($laporan->jenis_laporan),
                        'periode' => $tanggalMulai->format('Y-m-d') . ' - ' . $tanggalAkhir->format('Y-m-d'),
                        'total_pengaduan' => $totalPengaduan,
                        'pengaduan_selesai' => $pengaduanSelesai,
                        'pengaduan_proses' => $pengaduanProses,
                        'tanggal_dibuat' => now()->format('Y-m-d')
                    ]
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate laporan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download PDF laporan
     */
    public function downloadLaporan(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (pegawai)
            
            // Cari laporan
            $laporan = \App\Models\Laporan::where('id', $id)
                ->where('dibuat_oleh', $user->id)
                ->first();
            
            if (!$laporan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan tidak ditemukan'
                ], 404);
            }
            
            // Kalau file PDF udah ada, return link download
            if ($laporan->file_laporan) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'download_url' => asset('storage/' . $laporan->file_laporan),
                        'filename' => basename($laporan->file_laporan)
                    ]
                ], 200);
            }
            
            // Kalau belum ada, generate PDF dulu
            $filename = $this->generatePDFLaporan($laporan);
            
            // Update laporan dengan file PDF
            $laporan->update(['file_laporan' => $filename]);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF berhasil digenerate',
                'data' => [
                    'download_url' => asset('storage/' . $filename),
                    'filename' => basename($filename)
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal download laporan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF laporan (dummy untuk sekarang)
     */
    private function generatePDFLaporan($laporan): string
    {
        // TODO: Implement PDF generation using library like DomPDF
        // Untuk sekarang return dummy filename
        
        $filename = 'laporan/' . $laporan->jenis_laporan . '_' . 
                   $laporan->tanggal_mulai->format('Y-m-d') . '_' . 
                   $laporan->tanggal_selesai->format('Y-m-d') . '_' . 
                   time() . '.pdf';
        
        // Dummy: Create empty file for now
        $path = storage_path('app/public/' . $filename);
        
        // Ensure directory exists
        $dir = dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Create dummy PDF content (nanti ganti dengan real PDF generation)
        $dummyContent = "Laporan " . ucfirst($laporan->jenis_laporan) . "\n" .
                       "Periode: " . $laporan->tanggal_mulai->format('Y-m-d') . " - " . $laporan->tanggal_selesai->format('Y-m-d') . "\n" .
                       "Total Pengaduan: " . $laporan->total_pengaduan . "\n" .
                       "Pengaduan Selesai: " . $laporan->pengaduan_selesai . "\n" .
                       "Pengaduan Proses: " . $laporan->pengaduan_proses;
        
        file_put_contents($path, $dummyContent);
        
        return $filename;
    }

    /**
     * Ambil data profile pegawai
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (pegawai)
            
            // Ambil data lengkap user dengan relasi yang dibutuhkan
            $profile = User::with([
                'pengaduanSebagaiPegawai' => function($query) {
                    $query->select('id', 'pegawai_id', 'status', 'created_at')
                          ->orderBy('created_at', 'desc')
                          ->limit(5); // 5 pengaduan terakhir yang ditangani
                }
            ])->find($user->id);
            
            // Hitung statistik pegawai
            $statistics = [
                'total_pengaduan_ditangani' => Pengaduan::where('pegawai_id', $user->id)->count(),
                'pengaduan_selesai' => Pengaduan::where('pegawai_id', $user->id)
                    ->where('status', 'selesai')
                    ->count(),
                'pengaduan_proses' => Pengaduan::where('pegawai_id', $user->id)
                    ->whereIn('status', ['diproses', 'perlu_approval', 'disetujui'])
                    ->count(),
                'pengaduan_hari_ini' => Pengaduan::where('pegawai_id', $user->id)
                    ->whereDate('created_at', today())
                    ->count()
            ];
            
            // Format response
            $response = [
                'id' => $profile->id,
                'nama' => $profile->nama,
                'nik' => $profile->nik,
                'nip' => $profile->nip,
                'email' => $profile->email,
                'no_telepon' => $profile->no_telepon,
                'alamat' => $profile->alamat,
                'role' => $profile->role,
                'foto_profil' => $profile->foto_profil ? asset('storage/' . $profile->foto_profil) : null,
                
                // Statistik performa
                'statistics' => $statistics,
                
                // Pengaduan terakhir yang ditangani
                'pengaduan_terakhir' => $profile->pengaduanSebagaiPegawai->map(function ($pengaduan) {
                    return [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'judul' => $pengaduan->judul,
                        'status' => $pengaduan->status,
                        'tanggal' => $pengaduan->created_at->format('Y-m-d'),
                        'waktu_relatif' => $pengaduan->created_at->diffForHumans()
                    ];
                }),
                
                // Metadata
                'tanggal_bergabung' => $profile->created_at->format('Y-m-d'),
                'waktu_bergabung' => $profile->created_at->diffForHumans()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $response
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update data profile pegawai
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (pegawai)
            
            // Validasi input
            $request->validate([
                'nama' => 'sometimes|string|max:100',
                'nik' => 'sometimes|string|size:16|unique:users,nik,' . $user->id,
                'nip' => 'sometimes|nullable|string|max:20|unique:users,nip,' . $user->id,
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'no_telepon' => 'sometimes|string|max:20',
                'alamat' => 'sometimes|string|max:500',
                'password' => 'sometimes|string|min:6|confirmed',
                'foto_profil' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048'
            ]);
            
            // Data yang akan diupdate
            $updateData = $request->only([
                'nama', 'nik', 'nip', 'email', 'no_telepon', 'alamat'
            ]);
            
            // Handle password update
            if ($request->filled('password')) {
                $updateData['password'] = bcrypt($request->password);
            }
            
            // Handle foto profil upload
            if ($request->hasFile('foto_profil')) {
                $file = $request->file('foto_profil');
                $filename = 'profiles/' . time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                
                // Simpan file ke storage
                $path = $file->storeAs('public/' . dirname($filename), basename($filename));
                
                // Hapus foto lama jika ada
                if ($user->foto_profil && file_exists(storage_path('app/public/' . $user->foto_profil))) {
                    unlink(storage_path('app/public/' . $user->foto_profil));
                }
                
                $updateData['foto_profil'] = $filename;
            }
            
            // Update user
            $user->update($updateData);
            
            // Ambil data terbaru
            $updatedUser = User::find($user->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diupdate',
                'data' => [
                    'id' => $updatedUser->id,
                    'nama' => $updatedUser->nama,
                    'nik' => $updatedUser->nik,
                    'nip' => $updatedUser->nip,
                    'email' => $updatedUser->email,
                    'no_telepon' => $updatedUser->no_telepon,
                    'alamat' => $updatedUser->alamat,
                    'role' => $updatedUser->role,
                    'foto_profil' => $updatedUser->foto_profil ? asset('storage/' . $updatedUser->foto_profil) : null,
                    'updated_at' => $updatedUser->updated_at->toISOString()
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
                'message' => 'Gagal update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 