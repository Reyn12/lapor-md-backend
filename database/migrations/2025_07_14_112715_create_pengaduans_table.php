<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengaduans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pengaduan', 50)->unique();
            
            // Foreign keys to users table
            $table->foreignId('warga_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('pegawai_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('kepala_kantor_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Foreign key to kategori table (we'll create this later)
            $table->foreignId('kategori_id')->constrained('kategoris')->onDelete('cascade');
            
            $table->string('judul', 200);
            $table->text('deskripsi');
            $table->string('lokasi', 255);
            $table->string('foto_pengaduan', 255)->nullable();
            
            $table->enum('status', [
                'menunggu', 
                'diproses', 
                'perlu_approval', 
                'disetujui', 
                'ditolak', 
                'selesai'
            ])->default('menunggu');
            
            $table->timestamp('tanggal_pengaduan')->useCurrent();
            $table->timestamp('tanggal_proses')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            
            $table->text('catatan_pegawai')->nullable();
            $table->text('catatan_kepala_kantor')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaduans');
    }
};
