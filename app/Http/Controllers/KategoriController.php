<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KategoriController extends Controller
{
    /**
     * Ambil semua kategori untuk dropdown
     */
    public function index(): JsonResponse
    {
        try {
            $kategoris = Kategori::select('id', 'nama_kategori', 'deskripsi')
                ->orderBy('nama_kategori', 'asc')
                ->get()
                ->map(function ($kategori) {
                    return [
                        'id' => $kategori->id,
                        'nama_kategori' => $kategori->nama_kategori,
                        'deskripsi' => $kategori->deskripsi
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Data kategori berhasil diambil',
                'data' => [
                    'kategoris' => $kategoris,
                    'total' => $kategoris->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kategori',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 