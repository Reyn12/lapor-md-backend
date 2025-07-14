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
        Schema::create('laporans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dibuat_oleh')->constrained('users')->onDelete('cascade');
            $table->enum('jenis_laporan', ['harian', 'mingguan', 'bulanan', 'tahunan']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('total_pengaduan')->default(0);
            $table->integer('pengaduan_selesai')->default(0);
            $table->integer('pengaduan_proses')->default(0);
            $table->string('file_laporan', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporans');
    }
};
