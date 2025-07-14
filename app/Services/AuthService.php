<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Register new user (warga only)
     */
    public function register(array $data): array
    {
        $validator = Validator::make($data, [
            'nama' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'no_telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'foto_profil' => 'nullable|string|max:255',
        ], [
            // Custom error messages dalam bahasa Indonesia
            'nama.required' => 'Nama wajib diisi',
            'nama.max' => 'Nama maksimal 100 karakter',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak sama',
            'no_telepon.max' => 'Nomor telepon maksimal 20 karakter',
            'foto_profil.max' => 'Path foto profil maksimal 255 karakter',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Create user dengan role default 'warga'
        $user = User::create([
            'nama' => $data['nama'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'no_telepon' => $data['no_telepon'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'role' => 'warga',
            'foto_profil' => $data['foto_profil'] ?? null,
        ]);

        return [
            'success' => true,
            'message' => 'Registrasi berhasil',
            'user' => $user->makeHidden(['password'])
        ];
    }

    /**
     * Login user
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception('Email atau password salah');
        }

        // Generate tokens
        $tokens = $this->tokenService->generateTokenPair();

        // Update user dengan token baru
        $user->update($tokens);

        return [
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user->makeHidden(['password', 'access_token', 'refresh_token']),
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'access_expires_at' => $tokens['access_expires_at']->toISOString(),
                'refresh_expires_at' => $tokens['refresh_expires_at']->toISOString(),
            ]
        ];
    }

    /**
     * Refresh access token
     */
    public function refreshToken(string $refreshToken): array
    {
        $user = User::where('refresh_token', $refreshToken)->first();

        if (!$user || !$user->isRefreshTokenValid()) {
            throw new \Exception('Refresh token tidak valid atau expired');
        }

        // Generate token baru
        $tokens = $this->tokenService->generateTokenPair();
        $user->update($tokens);

        return [
            'success' => true,
            'message' => 'Token berhasil direfresh',
            'data' => [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'access_expires_at' => $tokens['access_expires_at']->toISOString(),
                'refresh_expires_at' => $tokens['refresh_expires_at']->toISOString(),
            ]
        ];
    }

    /**
     * Logout user
     */
    public function logout(User $user): array
    {
        $user->revokeTokens();

        return [
            'success' => true,
            'message' => 'Logout berhasil'
        ];
    }

    /**
     * Get user by access token
     */
    public function getUserByAccessToken(string $accessToken): ?User
    {
        return User::where('access_token', $accessToken)->first();
    }
} 