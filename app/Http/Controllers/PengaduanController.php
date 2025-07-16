<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\StatusPengaduan;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PengaduanController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'kategori_id' => 'required|exists:kategoris,id',
                'judul' => 'required|string|max:255',
                'deskripsi' => 'required|string',
                'lokasi' => 'required|string|max:255',
                'foto_pengaduan' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            ], [
                'kategori_id.required' => 'Kategori harus dipilih',
                'kategori_id.exists' => 'Kategori tidak valid',
                'judul.required' => 'Judul pengaduan harus diisi',
                'judul.max' => 'Judul maksimal 255 karakter',
                'deskripsi.required' => 'Deskripsi pengaduan harus diisi',
                'lokasi.required' => 'Lokasi harus diisi',
                'lokasi.max' => 'Lokasi maksimal 255 karakter',
                'foto_pengaduan.image' => 'File harus berupa gambar',
                'foto_pengaduan.mimes' => 'Format gambar harus jpeg, png, atau jpg',
                'foto_pengaduan.max' => 'Ukuran gambar maksimal 5MB',
            ]);

            // Get user yang login
            $user = $request->user();

            // Handle upload foto jika ada
            $fotoPath = null;
            if ($request->hasFile('foto_pengaduan')) {
                $file = $request->file('foto_pengaduan');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $fotoPath = $file->storeAs('pengaduan', $fileName, 'public');
            }

            DB::beginTransaction();

            // 1. Insert ke tabel pengaduan
            $pengaduan = Pengaduan::create([
                'warga_id' => $user->id,
                'kategori_id' => $validated['kategori_id'],
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'],
                'lokasi' => $validated['lokasi'],
                'foto_pengaduan' => $fotoPath,
                'status' => 'menunggu',
                'tanggal_pengaduan' => now(),
            ]);

            // 2. Insert ke tabel status_pengaduan
            StatusPengaduan::create([
                'pengaduan_id' => $pengaduan->id,
                'status' => 'menunggu',
                'keterangan' => 'Pengaduan baru dibuat oleh warga',
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            // 3. Insert ke tabel notifikasi untuk semua pegawai
            $pegawaiUsers = User::where('role', 'pegawai')->get();
            
            foreach ($pegawaiUsers as $pegawai) {
                Notifikasi::create([
                    'pengguna_id' => $pegawai->id,
                    'pengaduan_id' => $pengaduan->id,
                    'judul' => 'Pengaduan Baru',
                    'pesan' => 'Ada pengaduan baru dari warga yang perlu ditangani',
                    'dibaca' => false,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pengaduan berhasil dibuat',
                'data' => [
                    'pengaduan' => [
                        'id' => $pengaduan->id,
                        'nomor_pengaduan' => $pengaduan->nomor_pengaduan,
                        'judul' => $pengaduan->judul,
                        'status' => $pengaduan->status,
                        'tanggal_pengaduan' => $pengaduan->tanggal_pengaduan->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Hapus file yang sudah terupload jika ada error
            if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
                Storage::disk('public')->delete($fotoPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pengaduan: ' . $e->getMessage()
            ], 500);
        }
    }
} 