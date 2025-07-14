<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthTokenMiddleware
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get token dari Authorization header
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan'
            ], 401);
        }

        // Extract token
        $token = substr($authHeader, 7); // Remove "Bearer "

        // Cari user berdasarkan access token
        $user = $this->authService->getUserByAccessToken($token);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid'
            ], 401);
        }

        // Cek apakah token masih valid (belum expired)
        if (!$user->isAccessTokenValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Token sudah expired'
            ], 401);
        }

        // Set user ke request untuk digunakan di controller
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
} 