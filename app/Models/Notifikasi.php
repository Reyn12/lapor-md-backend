<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    protected $table = 'notifikasis';

    // Disable updated_at karena tabel cuma punya created_at
    const UPDATED_AT = null;

    protected $fillable = [
        'pengguna_id',
        'pengaduan_id',
        'judul',
        'pesan',
        'dibaca',
    ];

    protected function casts(): array
    {
        return [
            'dibaca' => 'boolean',
        ];
    }

    // Relasi ke User
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    // Relasi ke Pengaduan
    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class);
    }

    // Helper method untuk format waktu relatif
    public function getWaktuRelatifAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    // Scope untuk filter berdasarkan pengguna
    public function scopeByPengguna($query, $penggunaId)
    {
        return $query->where('pengguna_id', $penggunaId);
    }

    // Scope untuk notifikasi yang belum dibaca
    public function scopeUnread($query)
    {
        return $query->where('dibaca', false);
    }

    // Method untuk mark as read
    public function markAsRead()
    {
        $this->update(['dibaca' => true]);
    }
}
