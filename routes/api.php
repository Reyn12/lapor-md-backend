<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WargaController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PengaduanController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KepalaKantorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// PUBLIC ROUTES (No Authentication Required)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// PROTECTED ROUTES (Authentication Required)
Route::middleware(['auth.token'])->group(function () {
    
    // Auth related protected routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // WARGA ROUTES
Route::prefix('warga')->middleware(['role:warga'])->group(function () {
    Route::get('home', [WargaController::class, 'home']);
    Route::get('riwayat', [WargaController::class, 'riwayat']);
    Route::get('pengaduan/{id}', [WargaController::class, 'detailPengaduan']);
    Route::get('notifikasi', [WargaController::class, 'notifikasi']);
    Route::get('profile', [WargaController::class, 'profile']);
    Route::put('profile', [WargaController::class, 'updateProfile']);
    Route::post('pengaduan', [PengaduanController::class, 'store']);
});

    // PEGAWAI ROUTES
    Route::prefix('pegawai')->middleware(['role:pegawai'])->group(function () {
        Route::get('home', [PegawaiController::class, 'home']);
        Route::get('pengaduan', [PegawaiController::class, 'pengaduan']);
        Route::get('pengaduan/{id}', [PegawaiController::class, 'detailPengaduan']);
        Route::post('pengaduan/{id}/terima', [PegawaiController::class, 'terimaPengaduan']);
        Route::post('pengaduan/{id}/selesai', [PegawaiController::class, 'selesaikanPengaduan']);
        Route::post('pengaduan/{id}/ajukan-approval', [PegawaiController::class, 'ajukanApproval']);
        Route::get('laporan', [PegawaiController::class, 'laporan']);
        Route::post('laporan', [PegawaiController::class, 'generateLaporan']);
        Route::get('laporan/{id}/download', [PegawaiController::class, 'downloadLaporan']);
        Route::get('profile', [PegawaiController::class, 'profile']);
        Route::put('profile', [PegawaiController::class, 'updateProfile']);
    });

    // KEPALA KANTOR ROUTES
    Route::prefix('kepala-kantor')->middleware(['role:kepala_kantor'])->group(function () {
        Route::get('home', [KepalaKantorController::class, 'home']);
        Route::get('monitoring', [KepalaKantorController::class, 'monitoring']);
        Route::get('detail-pengaduan/{id}', [KepalaKantorController::class, 'detailPengaduan']);
        Route::get('approval', [KepalaKantorController::class, 'approval']);
        Route::post('pengaduan/{id}/approve', [KepalaKantorController::class, 'approvePengaduan']);
        Route::post('pengaduan/{id}/reject', [KepalaKantorController::class, 'rejectPengaduan']);
        Route::get('pegawai', [KepalaKantorController::class, 'kelolaPegawai']);
        Route::get('laporan', [KepalaKantorController::class, 'laporan']);
        Route::get('laporan/export-pdf', [KepalaKantorController::class, 'exportLaporanPDF']);
        Route::get('profile', [KepalaKantorController::class, 'profile']);
        Route::put('profile', [KepalaKantorController::class, 'updateProfile']);
    });

    // SHARED ROUTES (Accessible by multiple roles)
    Route::prefix('shared')->group(function () {
        // Routes yang bisa diakses multiple role
        // Contoh: notifikasi, profile update, dll
    });

    // KATEGORI ROUTES (Accessible by all authenticated users)
    Route::get('kategori', [KategoriController::class, 'index']);
}); 