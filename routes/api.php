<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WargaController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PengaduanController;
use App\Http\Controllers\KategoriController;
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
        Route::get('laporan', [PegawaiController::class, 'laporan']);
        Route::post('laporan', [PegawaiController::class, 'generateLaporan']);
        Route::get('laporan/{id}/download', [PegawaiController::class, 'downloadLaporan']);
        Route::get('profile', [PegawaiController::class, 'profile']);
        Route::put('profile', [PegawaiController::class, 'updateProfile']);
    });

    // KEPALA KANTOR ROUTES
    Route::prefix('kepala')->middleware(['role:kepala_kantor'])->group(function () {
        // Dashboard kepala kantor, kelola pegawai, laporan, dll
        // Route::get('dashboard', [KepalaKantorController::class, 'dashboard']);
        // Route::post('pegawai', [KepalaKantorController::class, 'buatPegawai']);
    });

    // SHARED ROUTES (Accessible by multiple roles)
    Route::prefix('shared')->group(function () {
        // Routes yang bisa diakses multiple role
        // Contoh: notifikasi, profile update, dll
    });

    // KATEGORI ROUTES (Accessible by all authenticated users)
    Route::get('kategori', [KategoriController::class, 'index']);
}); 