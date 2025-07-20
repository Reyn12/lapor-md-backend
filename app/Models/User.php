<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',  // Ganti dari 'name'
        'nik',   // NIK untuk semua user
        'nip',   // NIP khusus pegawai/kepala kantor
        'email',
        'password',
        'no_telepon',
        'alamat',
        'role',
        'foto_profil',
        'access_token',
        'refresh_token',
        'access_expires_at',
        'refresh_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'access_expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
        ];
    }

    // Token helpers
    public function isAccessTokenValid()
    {
        return $this->access_token && $this->access_expires_at && $this->access_expires_at->isFuture();
    }

    public function isRefreshTokenValid()
    {
        return $this->refresh_token && $this->refresh_expires_at && $this->refresh_expires_at->isFuture();
    }

    public function revokeTokens()
    {
        $this->update([
            'access_token' => null,
            'refresh_token' => null,
            'access_expires_at' => null,
            'refresh_expires_at' => null,
        ]);
    }

    // Role helpers
    public function isWarga()
    {
        return $this->role === 'warga';
    }

    public function isPegawai()
    {
        return $this->role === 'pegawai';
    }

    public function isKepalaKantor()
    {
        return $this->role === 'kepala_kantor';
    }

    // Relationships
    public function pengaduanSebagaiWarga()
    {
        return $this->hasMany(Pengaduan::class, 'warga_id');
    }

    public function pengaduanSebagaiPegawai() 
    {
        return $this->hasMany(Pengaduan::class, 'pegawai_id');
    }

    public function pengaduanSebagaiKepala()
    {
        return $this->hasMany(Pengaduan::class, 'kepala_kantor_id');
    }

    public function statusPengaduanHistory()
    {
        return $this->hasMany(StatusPengaduan::class, 'created_by');
    }

    public function notifikasis()
    {
        return $this->hasMany(Notifikasi::class, 'pengguna_id');
    }

    public function laporans()
    {
        return $this->hasMany(Laporan::class, 'dibuat_oleh');
    }

    public function notifikasiBelumDibaca()
    {
        return $this->hasMany(Notifikasi::class, 'pengguna_id')->belumDibaca();
    }
}
