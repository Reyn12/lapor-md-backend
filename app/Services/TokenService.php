<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;

class TokenService
{
    /**
     * Generate access token
     */
    public function generateAccessToken(): string
    {
        return 'access_' . Str::random(60);
    }

    /**
     * Generate refresh token
     */
    public function generateRefreshToken(): string
    {
        return 'refresh_' . Str::random(60);
    }

    /**
     * Get access token expiry time (1 hour from now)
     */
    public function getAccessTokenExpiry(): Carbon
    {
        return Carbon::now()->addHour();
    }

    /**
     * Get refresh token expiry time (7 days from now)
     */
    public function getRefreshTokenExpiry(): Carbon
    {
        return Carbon::now()->addDays(7);
    }

    /**
     * Generate both tokens with expiry
     */
    public function generateTokenPair(): array
    {
        return [
            'access_token' => $this->generateAccessToken(),
            'refresh_token' => $this->generateRefreshToken(),
            'access_expires_at' => $this->getAccessTokenExpiry(),
            'refresh_expires_at' => $this->getRefreshTokenExpiry(),
        ];
    }

    /**
     * Validate if token format is correct
     */
    public function isValidTokenFormat(string $token, string $type = 'access'): bool
    {
        $prefix = $type === 'access' ? 'access_' : 'refresh_';
        return Str::startsWith($token, $prefix) && strlen($token) === strlen($prefix) + 60;
    }
} 