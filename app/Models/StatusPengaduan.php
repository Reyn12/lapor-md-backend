<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusPengaduan extends Model
{
    use HasFactory;

    // Disable updated_at since we only need created_at for tracking
    public $timestamps = false;
    protected $dates = ['created_at'];

    protected $fillable = [
        'pengaduan_id',
        'status',
        'keterangan',
        'created_by'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    // Relationships
    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope methods
    public function scopeByPengaduan($query, $pengaduanId)
    {
        return $query->where('pengaduan_id', $pengaduanId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Helper method
    public static function createStatusHistory($pengaduanId, $status, $keterangan = null, $createdBy = null)
    {
        return self::create([
            'pengaduan_id' => $pengaduanId,
            'status' => $status,
            'keterangan' => $keterangan,
            'created_by' => $createdBy
        ]);
    }
}
