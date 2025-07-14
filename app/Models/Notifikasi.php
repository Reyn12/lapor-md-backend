<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    // Disable updated_at since we only need created_at for notifications
    public $timestamps = false;
    protected $dates = ['created_at'];

    protected $fillable = [
        'pengguna_id',
        'pengaduan_id',
        'judul',
        'pesan',
        'dibaca'
    ];

    protected $casts = [
        'dibaca' => 'boolean',
        'created_at' => 'datetime'
    ];

    // Relationships
    public function pengguna()
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function pengaduan()
    {
        return $this->belongsTo(Pengaduan::class, 'pengaduan_id');
    }

    // Scope methods
    public function scopeBelumDibaca($query)
    {
        return $query->where('dibaca', false);
    }

    public function scopeSudahDibaca($query)
    {
        return $query->where('dibaca', true);
    }

    public function scopeByPengguna($query, $penggunaId)
    {
        return $query->where('pengguna_id', $penggunaId);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Helper methods
    public function markAsRead()
    {
        $this->update(['dibaca' => true]);
    }

    public function markAsUnread()
    {
        $this->update(['dibaca' => false]);
    }

    public function isBelumDibaca()
    {
        return !$this->dibaca;
    }

    public function isSudahDibaca()
    {
        return $this->dibaca;
    }

    // Static methods for creating notifications
    public static function createNotifikasi($penggunaId, $pengaduanId, $judul, $pesan)
    {
        return self::create([
            'pengguna_id' => $penggunaId,
            'pengaduan_id' => $pengaduanId,
            'judul' => $judul,
            'pesan' => $pesan,
            'dibaca' => false
        ]);
    }

    public static function notifikasiStatusUpdate($pengaduanId, $statusBaru)
    {
        $pengaduan = Pengaduan::find($pengaduanId);
        if (!$pengaduan) return;

        // Notifikasi ke warga
        self::createNotifikasi(
            $pengaduan->warga_id,
            $pengaduanId,
            'Status Pengaduan Diperbarui',
            "Pengaduan '{$pengaduan->judul}' status berubah menjadi: {$statusBaru}"
        );

        // Notifikasi ke pegawai jika ada
        if ($pengaduan->pegawai_id) {
            self::createNotifikasi(
                $pengaduan->pegawai_id,
                $pengaduanId,
                'Update Status Pengaduan',
                "Status pengaduan '{$pengaduan->judul}' telah diperbarui menjadi: {$statusBaru}"
            );
        }
    }
}
