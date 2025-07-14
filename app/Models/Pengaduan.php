<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengaduan extends Model
{
    protected $table = 'pengaduans';

    protected $fillable = [
        'nomor_pengaduan',
        'warga_id',
        'pegawai_id',
        'kepala_kantor_id',
        'kategori_id',
        'judul',
        'deskripsi',
        'lokasi',
        'foto_pengaduan',
        'status',
        'tanggal_pengaduan',
        'tanggal_proses',
        'tanggal_selesai',
        'catatan_pegawai',
        'catatan_kepala_kantor',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengaduan' => 'datetime',
            'tanggal_proses' => 'datetime',
            'tanggal_selesai' => 'datetime',
        ];
    }

    // Relasi ke User (Warga)
    public function warga(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warga_id');
    }

    // Relasi ke User (Pegawai)
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pegawai_id');
    }

    // Relasi ke User (Kepala Kantor)
    public function kepalaKantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepala_kantor_id');
    }

    // Relasi ke Kategori
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    // Relasi ke StatusPengaduan
    public function statusHistory(): HasMany
    {
        return $this->hasMany(StatusPengaduan::class);
    }

    // Relasi ke Notifikasi
    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class);
    }

    // Helper method untuk format waktu relatif
    public function getWaktuRelatifAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    // Helper method untuk status badge color
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'menunggu' => 'warning',
            'diproses' => 'info',
            'perlu_approval' => 'secondary',
            'disetujui' => 'primary',
            'ditolak' => 'danger',
            'selesai' => 'success',
            default => 'secondary'
        };
    }

    // Scope untuk filter berdasarkan warga
    public function scopeByWarga($query, $wargaId)
    {
        return $query->where('warga_id', $wargaId);
    }

    // Scope untuk filter berdasarkan status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Auto generate nomor pengaduan
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pengaduan) {
            if (empty($pengaduan->nomor_pengaduan)) {
                $today = now()->format('Ymd');
                $count = static::whereDate('created_at', today())->count() + 1;
                $pengaduan->nomor_pengaduan = 'ADU-' . $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
