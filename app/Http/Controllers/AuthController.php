<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register warga baru
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->all());
            
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        try {
            $result = $this->authService->login(
                $request->email,
                $request->password
            );

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Refresh access token
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required|string'
        ]);

        try {
            $result = $this->authService->refreshToken($request->refresh_token);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // Akan di-set oleh middleware
            $result = $this->authService->logout($user);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get current user info
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user(); // Akan di-set oleh middleware

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->makeHidden(['password', 'access_token', 'refresh_token'])
            ]
        ], 200);
    }
} 