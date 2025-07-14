<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_kategori',
        'deskripsi'
    ];

    // Relationships
    public function pengaduans()
    {
        return $this->hasMany(Pengaduan::class);
    }

    // Accessor
    public function getNamaAttribute()
    {
        return $this->nama_kategori;
    }
}
