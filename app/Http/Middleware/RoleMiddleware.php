<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Roles yang diizinkan (misal: 'warga', 'pegawai', 'kepala_kantor')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Kalau user belum di-set (seharusnya ga mungkin kalau AuthTokenMiddleware jalan dulu)
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 401);
        }

        // Cek apakah role user ada di daftar roles yang diizinkan
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Role anda tidak memiliki izin untuk mengakses endpoint ini.',
                'required_roles' => $roles,
                'your_role' => $user->role
            ], 403);
        }

        return $next($request);
    }
} 