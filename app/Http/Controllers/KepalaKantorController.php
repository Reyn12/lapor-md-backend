<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\Notifikasi;
use App\Models\User;
use App\Models\Kategori;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KepalaKantorController extends Controller
{
    /**
     * Dashboard utama kepala kantor
     */
    public function home(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // User dari middleware (kepala_kantor)
            
            // 1. Executive Summary - Statistik utama
            $executiveSummary = [
                'total_pengaduan_bulan_ini' => Pengaduan::whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count(),
                'tingkat_penyelesaian' => $this->hitungTingkatPenyelesaian(),
                'rata_rata_waktu_proses' => $this->hitungRataRataWaktuProses(),
                'kategori_trending' => $this->getKategoriTrending()
            ];

            // 2. Status Pengaduan - Breakdown per status
            $statusPengaduan = $this->getStatusPengaduan();

            // 3. Grafik Pengaduan per Bulan (6 bulan terakhir)
            $grafikBulanan = $this->getGrafikBulanan();

            // 4. Data kepala kantor
            $dataKepalaKantor = [
                'nama' => $user->nama,
                'jabatan' => 'Kepala Dinas Pelayanan Masyarakat',
                'foto_profil' => $user->foto_profil
            ];

            // 5. Pengaduan yang perlu approval
            $pengaduanPerluApproval = Pengaduan::where('status', 'perlu_approval')
                ->with(['kategori', 'warga', 'pegawai'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($pengaduan) {
                    return [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'judul' => $pengaduan->judul,
                        'kategori' => $pengaduan->kategori ? $pengaduan->kategori->nama_kategori : null,
                        'warga_nama' => $pengaduan->warga ? $pengaduan->warga->nama : null,
                        'pegawai_nama' => $pengaduan->pegawai ? $pengaduan->pegawai->nama : null,
                        'lokasi' => $pengaduan->lokasi,
                        'tanggal_pengaduan' => $pengaduan->tanggal_pengaduan ? $pengaduan->tanggal_pengaduan->toISOString() : null,
                        'created_at' => $pengaduan->created_at->toISOString()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'executive_summary' => $executiveSummary,
                    'status_pengaduan' => $statusPengaduan,
                    'grafik_bulanan' => $grafikBulanan,
                    'kepala_kantor' => $dataKepalaKantor,
                    'pengaduan_perlu_approval' => $pengaduanPerluApproval
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dashboard kepala kantor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hitung tingkat penyelesaian pengaduan
     */
    private function hitungTingkatPenyelesaian(): float
    {
        $totalPengaduan = Pengaduan::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        $pengaduanSelesai = Pengaduan::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('status', 'selesai')
            ->count();
        
        if ($totalPengaduan == 0) return 0;
        
        return round(($pengaduanSelesai / $totalPengaduan) * 100, 1);
    }

    /**
     * Hitung rata-rata waktu proses pengaduan
     */
    private function hitungRataRataWaktuProses(): float
    {
        $pengaduanSelesai = Pengaduan::where('status', 'selesai')
            ->whereNotNull('tanggal_selesai')
            ->whereNotNull('tanggal_proses')
            ->get();
        
        if ($pengaduanSelesai->isEmpty()) return 0;
        
        $totalHari = 0;
        foreach ($pengaduanSelesai as $pengaduan) {
            $totalHari += $pengaduan->tanggal_proses->diffInDays($pengaduan->tanggal_selesai);
        }
        
        return round($totalHari / $pengaduanSelesai->count(), 1);
    }

    /**
     * Ambil kategori yang trending
     */
    private function getKategoriTrending(): array
    {
        $kategoriTrending = Pengaduan::select('kategori_id', DB::raw('count(*) as total'))
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('kategori_id')
            ->orderBy('total', 'desc')
            ->first();
        
        if (!$kategoriTrending) {
            return [
                'nama' => 'Tidak ada data',
                'total' => 0
            ];
        }
        
        $kategori = Kategori::find($kategoriTrending->kategori_id);
        
        return [
            'nama' => $kategori ? $kategori->nama_kategori : 'Tidak ada data',
            'total' => $kategoriTrending->total
        ];
    }

    /**
     * Ambil data grafik bulanan
     */
    private function getGrafikBulanan(): array
    {
        $bulanSekarang = Carbon::now();
        $dataGrafik = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $bulan = $bulanSekarang->copy()->subMonths($i);
            $totalPengaduan = Pengaduan::whereMonth('created_at', $bulan->month)
                ->whereYear('created_at', $bulan->year)
                ->count();
            
            $dataGrafik[] = [
                'bulan' => $bulan->format('M'), // Jan, Feb, dst
                'tahun' => $bulan->year,
                'total_pengaduan' => $totalPengaduan,
                'bulan_lengkap' => $bulan->format('F Y') // January 2024
            ];
        }
        
        return $dataGrafik;
    }

    /**
     * Ambil breakdown status pengaduan
     */
    private function getStatusPengaduan(): array
    {
        $totalPengaduan = Pengaduan::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        if ($totalPengaduan == 0) {
            return [
                'total' => 0,
                'status' => [
                    [
                        'nama' => 'Selesai',
                        'jumlah' => 0,
                        'persentase' => 0,
                        'warna' => 'green'
                    ],
                    [
                        'nama' => 'Diproses',
                        'jumlah' => 0,
                        'persentase' => 0,
                        'warna' => 'orange'
                    ],
                    [
                        'nama' => 'Menunggu',
                        'jumlah' => 0,
                        'persentase' => 0,
                        'warna' => 'blue'
                    ]
                ]
            ];
        }
        
        $selesai = Pengaduan::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('status', 'selesai')
            ->count();
        
        $diproses = Pengaduan::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereIn('status', ['diproses', 'perlu_approval', 'disetujui'])
            ->count();
        
        $menunggu = Pengaduan::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('status', 'menunggu')
            ->count();
        
        return [
            'total' => $totalPengaduan,
            'status' => [
                [
                    'nama' => 'Selesai',
                    'jumlah' => $selesai,
                    'persentase' => round(($selesai / $totalPengaduan) * 100, 1),
                    'warna' => 'green'
                ],
                [
                    'nama' => 'Diproses',
                    'jumlah' => $diproses,
                    'persentase' => round(($diproses / $totalPengaduan) * 100, 1),
                    'warna' => 'orange'
                ],
                [
                    'nama' => 'Menunggu',
                    'jumlah' => $menunggu,
                    'persentase' => round(($menunggu / $totalPengaduan) * 100, 1),
                    'warna' => 'blue'
                ]
            ]
        ];
    }

    /**
     * Ambil data pengaduan yang perlu approval
     */
    public function approval(Request $request): JsonResponse
    {
        try {
            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 10);
            $search = $request->query('search', '');
            $prioritas = $request->query('prioritas', 'semua');

            $pengaduanQuery = Pengaduan::where('status', 'perlu_approval')
                ->with(['kategori', 'warga', 'pegawai']);

            // Search functionality
            if (!empty($search)) {
                $pengaduanQuery->where(function($query) use ($search) {
                    $query->where('nomor_pengaduan', 'LIKE', "%{$search}%")
                          ->orWhere('judul', 'LIKE', "%{$search}%")
                          ->orWhere('lokasi', 'LIKE', "%{$search}%")
                          ->orWhereHas('warga', function($q) use ($search) {
                              $q->where('nama', 'LIKE', "%{$search}%");
                          })
                          ->orWhereHas('pegawai', function($q) use ($search) {
                              $q->where('nama', 'LIKE', "%{$search}%");
                          });
                });
            }

            // Filter by prioritas
            if ($prioritas !== 'semua') {
                switch ($prioritas) {
                    case 'high':
                        $pengaduanQuery->where('created_at', '>=', now()->subHours(24));
                        break;
                    case 'medium':
                        $pengaduanQuery->whereBetween('created_at', [now()->subDays(3), now()->subHours(24)]);
                        break;
                    case 'low':
                        $pengaduanQuery->where('created_at', '<', now()->subDays(3));
                        break;
                }
            }

            $totalItems = $pengaduanQuery->count();
            $totalPages = ceil($totalItems / $limit);

            $pengaduanList = $pengaduanQuery->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($pengaduan) {
                    return [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'judul' => $pengaduan->judul,
                        'kategori' => $pengaduan->kategori ? $pengaduan->kategori->nama_kategori : null,
                        'warga_nama' => $pengaduan->warga ? $pengaduan->warga->nama : null,
                        'pegawai_nama' => $pengaduan->pegawai ? $pengaduan->pegawai->nama : null,
                        'lokasi' => $pengaduan->lokasi,
                        'tanggal_pengaduan' => $pengaduan->tanggal_pengaduan ? $pengaduan->tanggal_pengaduan->toISOString() : null,
                        'rekomendasi' => $pengaduan->catatan_pegawai,
                        'deskripsi_lengkap' => $pengaduan->deskripsi,
                        'created_at' => $pengaduan->created_at->toISOString()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'pengaduan_list' => $pengaduanList,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_items' => $totalItems,
                        'per_page' => $limit
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengaduan yang perlu approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve pengaduan
     */
    public function approvePengaduan(Request $request, $id): JsonResponse
    {
        try {
            $pengaduan = Pengaduan::findOrFail($id);
            
            if ($pengaduan->status !== 'perlu_approval') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaduan tidak memerlukan approval'
                ], 400);
            }
            
            $catatan = $request->input('catatan', '');
            
            $pengaduan->status = 'disetujui';
            $pengaduan->catatan_kepala_kantor = $catatan;
            $pengaduan->save();
            
            // Buat notifikasi untuk pegawai
            Notifikasi::create([
                'pengguna_id' => $pengaduan->pegawai_id,
                'pengaduan_id' => $pengaduan->id,
                'judul' => 'Pengaduan Disetujui',
                'pesan' => "Pengaduan {$pengaduan->nomor_pengaduan} telah disetujui oleh kepala kantor."
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pengaduan berhasil disetujui'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject pengaduan
     */
    public function rejectPengaduan(Request $request, $id): JsonResponse
    {
        try {
            $pengaduan = Pengaduan::findOrFail($id);
            
            if ($pengaduan->status !== 'perlu_approval') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaduan tidak memerlukan approval'
                ], 400);
            }
            
            $catatan = $request->input('catatan', '');
            
            $pengaduan->status = 'diproses'; // Ubah dari 'ditolak' menjadi 'diproses'
            $pengaduan->catatan_kepala_kantor = $catatan;
            $pengaduan->save();
            
            // Buat notifikasi untuk pegawai
            Notifikasi::create([
                'pengguna_id' => $pengaduan->pegawai_id,
                'pengaduan_id' => $pengaduan->id,
                'judul' => 'Pengaduan Dikembalikan',
                'pesan' => "Pengaduan {$pengaduan->nomor_pengaduan} dikembalikan oleh kepala kantor untuk direvisi. Catatan: {$catatan}"
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pengaduan berhasil ditolak'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses rejection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kelola pegawai
     */
    public function kelolaPegawai(Request $request): JsonResponse
    {
        try {
            $pegawai = User::where('role', 'pegawai')
                ->select('id', 'nama', 'nip', 'email', 'no_telepon', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $pegawai
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pegawai',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Monitoring real-time dashboard
     */
    public function monitoring(Request $request): JsonResponse
    {
        try {
            // 1. Real-time statistics
            $realTimeStats = [
                'total_pengaduan_all_time' => Pengaduan::count(),
                'pengaduan_aktif' => Pengaduan::whereIn('status', ['menunggu', 'diproses', 'perlu_approval', 'disetujui'])->count(),
                'performance_pegawai' => $this->hitungPerformancePegawai()
            ];

            // 2. List semua pengaduan dengan detail
            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 10);
            $status = $request->query('status', 'semua');
            $search = $request->query('search', '');
            $prioritas = $request->query('prioritas', 'semua');

            $pengaduanQuery = Pengaduan::with(['kategori', 'warga', 'pegawai']);

            // Filter by status
            if ($status !== 'semua') {
                $pengaduanQuery->where('status', $status);
            }

            // Search functionality
            if (!empty($search)) {
                $pengaduanQuery->where(function($query) use ($search) {
                    $query->where('nomor_pengaduan', 'LIKE', "%{$search}%")
                          ->orWhere('judul', 'LIKE', "%{$search}%")
                          ->orWhere('lokasi', 'LIKE', "%{$search}%")
                          ->orWhereHas('warga', function($q) use ($search) {
                              $q->where('nama', 'LIKE', "%{$search}%");
                          })
                          ->orWhereHas('pegawai', function($q) use ($search) {
                              $q->where('nama', 'LIKE', "%{$search}%");
                          });
                });
            }

            // Filter by prioritas
            if ($prioritas !== 'semua') {
                switch ($prioritas) {
                    case 'high':
                        $pengaduanQuery->where('created_at', '>=', now()->subHours(24));
                        break;
                    case 'medium':
                        $pengaduanQuery->whereBetween('created_at', [now()->subDays(3), now()->subHours(24)]);
                        break;
                    case 'low':
                        $pengaduanQuery->where('created_at', '<', now()->subDays(3));
                        break;
                }
            }

            $totalItems = $pengaduanQuery->count();
            $totalPages = ceil($totalItems / $limit);

            $pengaduanList = $pengaduanQuery->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($pengaduan) {
                    return [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'judul' => $pengaduan->judul,
                        'status' => $pengaduan->status,
                        'prioritas' => $this->tentukanPrioritas($pengaduan->created_at),
                        'kategori' => $pengaduan->kategori ? $pengaduan->kategori->nama_kategori : null,
                        'warga_nama' => $pengaduan->warga ? $pengaduan->warga->nama : null,
                        'pegawai_nama' => $pengaduan->pegawai ? $pengaduan->pegawai->nama : null,
                        'lokasi' => $pengaduan->lokasi,
                        'tanggal_pengaduan' => $pengaduan->tanggal_pengaduan ? $pengaduan->tanggal_pengaduan->toISOString() : null,
                        'progress' => $this->hitungProgress($pengaduan),
                        'created_at' => $pengaduan->created_at->toISOString()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'real_time_stats' => $realTimeStats,
                    'pengaduan_list' => $pengaduanList,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_items' => $totalItems,
                        'per_page' => $limit
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data monitoring',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail pengaduan untuk kepala kantor
     */
    public function detailPengaduan(Request $request, $id): JsonResponse
    {
        try {
            $pengaduan = Pengaduan::with(['kategori', 'warga', 'pegawai', 'statusHistory'])
                ->findOrFail($id);

            $data = [
                'id' => $pengaduan->id,
                'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                'judul' => $pengaduan->judul,
                'deskripsi' => $pengaduan->deskripsi,
                'lokasi' => $pengaduan->lokasi,
                'status' => $pengaduan->status,
                'prioritas' => $this->tentukanPrioritas($pengaduan->created_at),
                'foto_pengaduan' => $pengaduan->foto_pengaduan,
                'tanggal_pengaduan' => $pengaduan->tanggal_pengaduan ? $pengaduan->tanggal_pengaduan->toISOString() : null,
                'tanggal_proses' => $pengaduan->tanggal_proses ? $pengaduan->tanggal_proses->toISOString() : null,
                'tanggal_selesai' => $pengaduan->tanggal_selesai ? $pengaduan->tanggal_selesai->toISOString() : null,
                'catatan_pegawai' => $pengaduan->catatan_pegawai,
                'catatan_kepala_kantor' => $pengaduan->catatan_kepala_kantor,
                'created_at' => $pengaduan->created_at->toISOString(),
                'updated_at' => $pengaduan->updated_at->toISOString(),
                'kategori' => $pengaduan->kategori ? [
                    'id' => $pengaduan->kategori->id,
                    'nama' => $pengaduan->kategori->nama_kategori,
                    'deskripsi' => $pengaduan->kategori->deskripsi
                ] : null,
                'warga' => $pengaduan->warga ? [
                    'id' => $pengaduan->warga->id,
                    'nama' => $pengaduan->warga->nama,
                    'nik' => $pengaduan->warga->nik,
                    'no_telepon' => $pengaduan->warga->no_telepon,
                    'alamat' => $pengaduan->warga->alamat
                ] : null,
                'pegawai' => $pengaduan->pegawai ? [
                    'id' => $pengaduan->pegawai->id,
                    'nama' => $pengaduan->pegawai->nama,
                    'nip' => $pengaduan->pegawai->nip,
                    'email' => $pengaduan->pegawai->email,
                    'no_telepon' => $pengaduan->pegawai->no_telepon
                ] : null,
                'riwayat_status' => $pengaduan->statusHistory->map(function ($status) {
                    return [
                        'status' => $status->status,
                        'keterangan' => $status->keterangan,
                        'created_at' => $status->created_at->toISOString(),
                        'created_by' => $status->created_by
                    ];
                }),
                'progress' => $this->hitungProgress($pengaduan)
            ];

            return response()->json([
                'success' => true,
                'data' => $data
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
     * Hitung performance pegawai
     */
    private function hitungPerformancePegawai(): float
    {
        $totalPengaduan = Pengaduan::whereNotNull('pegawai_id')->count();
        
        if ($totalPengaduan == 0) return 0;
        
        $pengaduanSelesai = Pengaduan::whereNotNull('pegawai_id')
            ->where('status', 'selesai')
            ->count();
        
        return round(($pengaduanSelesai / $totalPengaduan) * 100, 1);
    }

    /**
     * Tentukan prioritas berdasarkan waktu
     */
    private function tentukanPrioritas($createdAt): string
    {
        $hoursDiff = Carbon::now()->diffInHours($createdAt);
        
        if ($hoursDiff <= 24) {
            return 'HIGH';
        } elseif ($hoursDiff <= 72) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }

    /**
     * Hitung progress pengaduan
     */
    private function hitungProgress($pengaduan): array
    {
        $progress = 0;
        $statusText = '';
        
        switch ($pengaduan->status) {
            case 'menunggu':
                $progress = 10;
                $statusText = 'Menunggu Penanganan';
                break;
            case 'diproses':
                $progress = 50;
                $statusText = 'Sedang Diproses';
                break;
            case 'perlu_approval':
                $progress = 75;
                $statusText = 'Menunggu Approval';
                break;
            case 'disetujui':
                $progress = 85;
                $statusText = 'Disetujui';
                break;
            case 'selesai':
                $progress = 100;
                $statusText = 'Selesai';
                break;
            case 'ditolak':
                $progress = 0;
                $statusText = 'Ditolak';
                break;
        }
        
        return [
            'persentase' => $progress,
            'status_text' => $statusText
        ];
    }

    /**
     * Laporan analytics untuk kepala kantor
     */
    public function laporan(Request $request): JsonResponse
    {
        try {
            $periode = $request->query('periode', 'bulan_ini'); // bulan_ini, 3_bulan, 6_bulan, tahun_ini
            
            // 1. Performance Metrics
            $performanceMetrics = [
                'tingkat_efisiensi' => $this->hitungTingkatEfisiensi($periode),
                'waktu_respon' => $this->hitungWaktuRespon($periode),
                'skor_kepuasan' => $this->hitungSkorKepuasan($periode)
            ];

            // 2. Trend Analysis
            $trendAnalysis = [
                'trend_volume' => $this->hitungTrendVolume($periode),
                'trend_penyelesaian' => $this->hitungTrendPenyelesaian($periode),
                'trend_efisiensi_biaya' => $this->hitungTrendEfisiensiBiaya($periode)
            ];

            // 3. Data Laporan yang sudah dibuat
            $laporanList = Laporan::orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($laporan) {
                    return [
                        'id' => $laporan->id,
                        'jenis_laporan' => $laporan->jenis_laporan,
                        'tanggal_mulai' => $laporan->tanggal_mulai,
                        'tanggal_selesai' => $laporan->tanggal_selesai,
                        'total_pengaduan' => $laporan->total_pengaduan,
                        'pengaduan_selesai' => $laporan->pengaduan_selesai,
                        'pengaduan_proses' => $laporan->pengaduan_proses,
                        'file_laporan' => $laporan->file_laporan,
                        'created_at' => $laporan->created_at->toISOString()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'performance_metrics' => $performanceMetrics,
                    'trend_analysis' => $trendAnalysis,
                    'laporan_list' => $laporanList
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
     * Export laporan ke PDF
     */
    public function exportLaporanPDF(Request $request): JsonResponse
    {
        try {
            $periode = $request->query('periode', 'bulan_ini');
            $jenisLaporan = $request->query('jenis', 'bulanan');
            
            // Generate laporan baru
            $laporan = $this->generateLaporanBaru($periode, $jenisLaporan);
            
            // Generate PDF
            $pdfPath = $this->generatePDFLaporan($laporan);
            
            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil di-generate',
                'data' => [
                    'laporan_id' => $laporan->id,
                    'file_url' => $pdfPath,
                    'download_url' => url('storage/laporan/' . basename($pdfPath))
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal export laporan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hitung tingkat efisiensi
     */
    private function hitungTingkatEfisiensi($periode): float
    {
        $query = $this->getQueryByPeriode($periode);
        
        $totalPengaduan = $query->count();
        if ($totalPengaduan == 0) return 0;
        
        $pengaduanSelesai = $query->where('status', 'selesai')->count();
        $pengaduanDitolak = $query->where('status', 'ditolak')->count();
        
        // Efisiensi = (selesai + ditolak) / total * 100
        return round((($pengaduanSelesai + $pengaduanDitolak) / $totalPengaduan) * 100, 1);
    }

    /**
     * Hitung waktu respon rata-rata
     */
    private function hitungWaktuRespon($periode): float
    {
        $query = $this->getQueryByPeriode($periode);
        
        $pengaduanDenganRespon = $query->whereNotNull('tanggal_proses')
            ->whereNotNull('tanggal_pengaduan')
            ->get();
        
        if ($pengaduanDenganRespon->isEmpty()) return 0;
        
        $totalHari = 0;
        foreach ($pengaduanDenganRespon as $pengaduan) {
            $totalHari += $pengaduan->tanggal_pengaduan->diffInDays($pengaduan->tanggal_proses);
        }
        
        return round($totalHari / $pengaduanDenganRespon->count(), 1);
    }

    /**
     * Hitung skor kepuasan (simulasi)
     */
    private function hitungSkorKepuasan($periode): float
    {
        // Simulasi skor kepuasan berdasarkan tingkat penyelesaian
        $tingkatPenyelesaian = $this->hitungTingkatPenyelesaian();
        
        if ($tingkatPenyelesaian >= 90) return 4.7;
        elseif ($tingkatPenyelesaian >= 80) return 4.5;
        elseif ($tingkatPenyelesaian >= 70) return 4.2;
        elseif ($tingkatPenyelesaian >= 60) return 3.8;
        else return 3.5;
    }

    /**
     * Hitung trend volume
     */
    private function hitungTrendVolume($periode): array
    {
        $periodeSekarang = $this->getQueryByPeriode($periode)->count();
        $periodeSebelumnya = $this->getQueryByPeriode($periode, true)->count();
        
        if ($periodeSebelumnya == 0) {
            return ['persentase' => 0, 'trend' => 'stabil'];
        }
        
        $persentase = round((($periodeSekarang - $periodeSebelumnya) / $periodeSebelumnya) * 100, 1);
        
        return [
            'persentase' => $persentase,
            'trend' => $persentase > 0 ? 'naik' : ($persentase < 0 ? 'turun' : 'stabil')
        ];
    }

    /**
     * Hitung trend penyelesaian
     */
    private function hitungTrendPenyelesaian($periode): array
    {
        $penyelesaianSekarang = $this->hitungTingkatPenyelesaian();
        $penyelesaianSebelumnya = $this->hitungTingkatPenyelesaianSebelumnya($periode);
        
        $persentase = round($penyelesaianSekarang - $penyelesaianSebelumnya, 1);
        
        return [
            'persentase' => $persentase,
            'trend' => $persentase > 0 ? 'naik' : ($persentase < 0 ? 'turun' : 'stabil')
        ];
    }

    /**
     * Hitung trend efisiensi biaya
     */
    private function hitungTrendEfisiensiBiaya($periode): array
    {
        // Simulasi trend efisiensi biaya
        $efisiensiSekarang = $this->hitungTingkatEfisiensi($periode);
        $efisiensiSebelumnya = $this->hitungTingkatEfisiensiSebelumnya($periode);
        
        $persentase = round($efisiensiSekarang - $efisiensiSebelumnya, 1);
        
        return [
            'persentase' => $persentase,
            'trend' => $persentase > 0 ? 'naik' : ($persentase < 0 ? 'turun' : 'stabil')
        ];
    }

    /**
     * Helper untuk query berdasarkan periode
     */
    private function getQueryByPeriode($periode, $sebelumnya = false)
    {
        $query = Pengaduan::query();
        
        switch ($periode) {
            case 'bulan_ini':
                if ($sebelumnya) {
                    $query->whereMonth('created_at', Carbon::now()->subMonth()->month)
                          ->whereYear('created_at', Carbon::now()->subMonth()->year);
                } else {
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                }
                break;
            case '3_bulan':
                if ($sebelumnya) {
                    $query->where('created_at', '>=', Carbon::now()->subMonths(6))
                          ->where('created_at', '<', Carbon::now()->subMonths(3));
                } else {
                    $query->where('created_at', '>=', Carbon::now()->subMonths(3));
                }
                break;
            case '6_bulan':
                if ($sebelumnya) {
                    $query->where('created_at', '>=', Carbon::now()->subMonths(12))
                          ->where('created_at', '<', Carbon::now()->subMonths(6));
                } else {
                    $query->where('created_at', '>=', Carbon::now()->subMonths(6));
                }
                break;
            case 'tahun_ini':
                if ($sebelumnya) {
                    $query->whereYear('created_at', Carbon::now()->subYear()->year);
                } else {
                    $query->whereYear('created_at', Carbon::now()->year);
                }
                break;
        }
        
        return $query;
    }

    /**
     * Hitung tingkat penyelesaian periode sebelumnya
     */
    private function hitungTingkatPenyelesaianSebelumnya($periode): float
    {
        $query = $this->getQueryByPeriode($periode, true);
        
        $totalPengaduan = $query->count();
        if ($totalPengaduan == 0) return 0;
        
        $pengaduanSelesai = $query->where('status', 'selesai')->count();
        
        return round(($pengaduanSelesai / $totalPengaduan) * 100, 1);
    }

    /**
     * Hitung tingkat efisiensi periode sebelumnya
     */
    private function hitungTingkatEfisiensiSebelumnya($periode): float
    {
        $query = $this->getQueryByPeriode($periode, true);
        
        $totalPengaduan = $query->count();
        if ($totalPengaduan == 0) return 0;
        
        $pengaduanSelesai = $query->where('status', 'selesai')->count();
        $pengaduanDitolak = $query->where('status', 'ditolak')->count();
        
        return round((($pengaduanSelesai + $pengaduanDitolak) / $totalPengaduan) * 100, 1);
    }

    /**
     * Generate laporan baru
     */
    private function generateLaporanBaru($periode, $jenisLaporan): Laporan
    {
        $query = $this->getQueryByPeriode($periode);
        
        $totalPengaduan = $query->count();
        $pengaduanSelesai = $query->where('status', 'selesai')->count();
        $pengaduanProses = $query->whereIn('status', ['diproses', 'perlu_approval', 'disetujui'])->count();
        
        // Tentukan tanggal range
        $tanggalRange = $this->getTanggalRange($periode);
        
        return Laporan::create([
            'dibuat_oleh' => request()->user()->id,
            'jenis_laporan' => $jenisLaporan,
            'tanggal_mulai' => $tanggalRange['mulai'],
            'tanggal_selesai' => $tanggalRange['selesai'],
            'total_pengaduan' => $totalPengaduan,
            'pengaduan_selesai' => $pengaduanSelesai,
            'pengaduan_proses' => $pengaduanProses,
            'file_laporan' => null // Akan diupdate setelah generate PDF
        ]);
    }

    /**
     * Get tanggal range berdasarkan periode
     */
    private function getTanggalRange($periode): array
    {
        switch ($periode) {
            case 'bulan_ini':
                return [
                    'mulai' => Carbon::now()->startOfMonth(),
                    'selesai' => Carbon::now()->endOfMonth()
                ];
            case '3_bulan':
                return [
                    'mulai' => Carbon::now()->subMonths(3)->startOfMonth(),
                    'selesai' => Carbon::now()->endOfMonth()
                ];
            case '6_bulan':
                return [
                    'mulai' => Carbon::now()->subMonths(6)->startOfMonth(),
                    'selesai' => Carbon::now()->endOfMonth()
                ];
            case 'tahun_ini':
                return [
                    'mulai' => Carbon::now()->startOfYear(),
                    'selesai' => Carbon::now()->endOfYear()
                ];
            default:
                return [
                    'mulai' => Carbon::now()->startOfMonth(),
                    'selesai' => Carbon::now()->endOfMonth()
                ];
        }
    }

    /**
     * Generate PDF laporan
     */
    private function generatePDFLaporan($laporan): string
    {
        // Simulasi generate PDF
        $filename = 'laporan_' . $laporan->jenis_laporan . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $filepath = storage_path('app/public/laporan/' . $filename);
        
        // Pastikan direktori ada
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        // Simulasi file PDF (dalam real implementation, gunakan library seperti DomPDF)
        file_put_contents($filepath, 'Simulasi PDF Laporan');
        
        // Update file_laporan di database
        $laporan->update(['file_laporan' => $filename]);
        
        return $filepath;
    }

    /**
     * Ambil data profile kepala kantor
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Ambil statistik untuk profile
            $statistik = [
                'total_pengaduan_disetujui' => Pengaduan::where('kepala_kantor_id', $user->id)
                    ->where('status', 'disetujui')
                    ->count(),
                'total_pengaduan_ditolak' => Pengaduan::where('kepala_kantor_id', $user->id)
                    ->where('status', 'ditolak')
                    ->count(),
                'total_laporan_dibuat' => Laporan::where('dibuat_oleh', $user->id)->count(),
                'rata_rata_waktu_approval' => $this->hitungRataRataWaktuApproval($user->id)
            ];

            // Ambil riwayat approval terbaru
            $riwayatApproval = Pengaduan::where('kepala_kantor_id', $user->id)
                ->whereIn('status', ['disetujui', 'ditolak'])
                ->with(['kategori', 'warga', 'pegawai'])
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
                        'pegawai_nama' => $pengaduan->pegawai ? $pengaduan->pegawai->nama : null,
                        'catatan_kepala_kantor' => $pengaduan->catatan_kepala_kantor,
                        'updated_at' => $pengaduan->updated_at->toISOString()
                    ];
                });

            $data = [
                'id' => $user->id,
                'nama' => $user->nama,
                'nik' => $user->nik,
                'nip' => $user->nip,
                'email' => $user->email,
                'no_telepon' => $user->no_telepon,
                'alamat' => $user->alamat,
                'role' => $user->role,
                'foto_profil' => $user->foto_profil,
                'jabatan' => 'Kepala Dinas Pelayanan Masyarakat',
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
                'statistik' => $statistik,
                'riwayat_approval' => $riwayatApproval
            ];

            return response()->json([
                'success' => true,
                'data' => $data
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
     * Update profile kepala kantor
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Validasi input
            $request->validate([
                'nama' => 'sometimes|string|max:100',
                'email' => 'sometimes|email|max:100|unique:users,email,' . $user->id,
                'no_telepon' => 'sometimes|string|max:20',
                'alamat' => 'sometimes|string',
                'password' => 'sometimes|string|min:6'
            ]);

            // Update data user
            $updateData = $request->only(['nama', 'email', 'no_telepon', 'alamat']);
            
            // Update password jika ada
            if ($request->filled('password')) {
                $updateData['password'] = bcrypt($request->password);
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diupdate',
                'data' => [
                    'id' => $user->id,
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_telepon' => $user->no_telepon,
                    'alamat' => $user->alamat,
                    'foto_profil' => $user->foto_profil,
                    'updated_at' => $user->updated_at->toISOString()
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

    /**
     * Hitung rata-rata waktu approval
     */
    private function hitungRataRataWaktuApproval($kepalaKantorId): float
    {
        $pengaduanApproved = Pengaduan::where('kepala_kantor_id', $kepalaKantorId)
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->whereNotNull('catatan_kepala_kantor')
            ->get();

        if ($pengaduanApproved->isEmpty()) return 0;

        $totalHari = 0;
        foreach ($pengaduanApproved as $pengaduan) {
            // Hitung waktu dari pengaduan dibuat sampai di-approve
            $totalHari += $pengaduan->created_at->diffInDays($pengaduan->updated_at);
        }

        return round($totalHari / $pengaduanApproved->count(), 1);
    }
} 