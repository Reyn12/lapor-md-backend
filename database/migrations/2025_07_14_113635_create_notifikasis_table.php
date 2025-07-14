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
        Schema::create('notifikasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('pengaduan_id')->nullable()->constrained('pengaduans')->onDelete('cascade');
            $table->string('judul', 200);
            $table->text('pesan');
            $table->boolean('dibaca')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasis');
    }
};
