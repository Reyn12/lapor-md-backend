<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Laporan extends Model
{
    use HasFactory;

    // Disable updated_at since we only need created_at for reports
    public $timestamps = false;
    protected $dates = ['created_at', 'tanggal_mulai', 'tanggal_selesai'];

    protected $fillable = [
        'dibuat_oleh',
        'jenis_laporan',
        'tanggal_mulai',
        'tanggal_selesai',
        'total_pengaduan',
        'pengaduan_selesai',
        'pengaduan_proses',
        'file_laporan'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'total_pengaduan' => 'integer',
        'pengaduan_selesai' => 'integer',
        'pengaduan_proses' => 'integer'
    ];

    // Relationships
    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    // Scope methods
    public function scopeByJenis($query, $jenis)
    {
        return $query->where('jenis_laporan', $jenis);
    }

    public function scopeByPembuat($query, $pembuatId)
    {
        return $query->where('dibuat_oleh', $pembuatId);
    }

    public function scopeByPeriode($query, $tanggalMulai, $tanggalSelesai)
    {
        return $query->whereBetween('tanggal_mulai', [$tanggalMulai, $tanggalSelesai]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Helper methods
    public function isHarian()
    {
        return $this->jenis_laporan === 'harian';
    }

    public function isMingguan()
    {
        return $this->jenis_laporan === 'mingguan';
    }

    public function isBulanan()
    {
        return $this->jenis_laporan === 'bulanan';
    }

    public function isTahunan()
    {
        return $this->jenis_laporan === 'tahunan';
    }

    public function getPersentaseSelesai()
    {
        if ($this->total_pengaduan == 0) return 0;
        return round(($this->pengaduan_selesai / $this->total_pengaduan) * 100, 2);
    }

    public function getPersentaseProses()
    {
        if ($this->total_pengaduan == 0) return 0;
        return round(($this->pengaduan_proses / $this->total_pengaduan) * 100, 2);
    }

    public function getDurasiPeriode()
    {
        return $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1;
    }

    // Static methods for generating reports
    public static function generateLaporan($jenisLaporan, $pembuatId, $tanggalMulai = null, $tanggalSelesai = null)
    {
        // Set periode berdasarkan jenis laporan jika tidak ada tanggal
        if (!$tanggalMulai || !$tanggalSelesai) {
            [$tanggalMulai, $tanggalSelesai] = self::getPeriodeLaporan($jenisLaporan);
        }

        // Hitung statistik pengaduan
        $totalPengaduan = Pengaduan::whereBetween('tanggal_pengaduan', [$tanggalMulai, $tanggalSelesai])->count();
        $pengaduanSelesai = Pengaduan::whereBetween('tanggal_pengaduan', [$tanggalMulai, $tanggalSelesai])
                                    ->where('status', 'selesai')->count();
        $pengaduanProses = Pengaduan::whereBetween('tanggal_pengaduan', [$tanggalMulai, $tanggalSelesai])
                                   ->whereIn('status', ['diproses', 'perlu_approval'])->count();

        // Buat laporan
        return self::create([
            'dibuat_oleh' => $pembuatId,
            'jenis_laporan' => $jenisLaporan,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'total_pengaduan' => $totalPengaduan,
            'pengaduan_selesai' => $pengaduanSelesai,
            'pengaduan_proses' => $pengaduanProses
        ]);
    }

    private static function getPeriodeLaporan($jenisLaporan)
    {
        $sekarang = Carbon::now();
        
        switch ($jenisLaporan) {
            case 'harian':
                return [$sekarang->startOfDay(), $sekarang->endOfDay()];
            case 'mingguan':
                return [$sekarang->startOfWeek(), $sekarang->endOfWeek()];
            case 'bulanan':
                return [$sekarang->startOfMonth(), $sekarang->endOfMonth()];
            case 'tahunan':
                return [$sekarang->startOfYear(), $sekarang->endOfYear()];
            default:
                return [$sekarang->startOfDay(), $sekarang->endOfDay()];
        }
    }
}
