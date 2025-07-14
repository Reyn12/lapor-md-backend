<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaduan extends Model
{
    use HasFactory;

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
        'catatan_kepala_kantor'
    ];

    protected $casts = [
        'tanggal_pengaduan' => 'datetime',
        'tanggal_proses' => 'datetime', 
        'tanggal_selesai' => 'datetime',
    ];

    // Relationships
    public function warga()
    {
        return $this->belongsTo(User::class, 'warga_id');
    }

    public function pegawai()
    {
        return $this->belongsTo(User::class, 'pegawai_id');
    }

    public function kepalaKantor()
    {
        return $this->belongsTo(User::class, 'kepala_kantor_id');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(StatusPengaduan::class)->latest();
    }

    public function latestStatus()
    {
        return $this->hasOne(StatusPengaduan::class)->latestOfMany();
    }

    public function notifikasis()
    {
        return $this->hasMany(Notifikasi::class, 'pengaduan_id');
    }

    // Status helpers
    public function isMenunggu()
    {
        return $this->status === 'menunggu';
    }

    public function isDiproses()
    {
        return $this->status === 'diproses';
    }

    public function isPerluApproval()
    {
        return $this->status === 'perlu_approval';
    }

    public function isDisetujui()
    {
        return $this->status === 'disetujui';
    }

    public function isDitolak()
    {
        return $this->status === 'ditolak';
    }

    public function isSelesai()
    {
        return $this->status === 'selesai';
    }

    // Scope methods
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByWarga($query, $wargaId)
    {
        return $query->where('warga_id', $wargaId);
    }

    public function scopeByPegawai($query, $pegawaiId)
    {
        return $query->where('pegawai_id', $pegawaiId);
    }

    // Auto generate nomor pengaduan
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pengaduan) {
            if (empty($pengaduan->nomor_pengaduan)) {
                $pengaduan->nomor_pengaduan = 'ADU-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1, 
                    4, 
                    '0', 
                    STR_PAD_LEFT
                );
            }
        });
    }
}
