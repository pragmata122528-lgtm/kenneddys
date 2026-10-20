<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new customer or vendor user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? UserRole::Customer->value,
            'is_active' => true,
        ]);

        // If vendor registered with custom shop details, update the auto-created profile
        if ($user->role === UserRole::Vendor->value && (! empty($validated['shop_name']) || ! empty($validated['shop_description']))) {
            $user->vendor()->update(array_filter([
                'shop_name' => $validated['shop_name'] ?? null,
                'shop_description' => $validated['shop_description'] ?? null,
            ]));
        }

        $token = auth('api')->login($user);

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => new UserResource($user->load('vendor')),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 201);
    }

    /**
     * Authenticate user and issue JWT token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password credentials.',
                'code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->is_active) {
            auth('api')->logout();

            return response()->json([
                'message' => 'Your account is deactivated. Please contact support.',
                'code' => 'ACCOUNT_DEACTIVATED',
            ], 403);
        }

        return response()->json([
            'message' => 'Login successful.',
            'user' => new UserResource($user->load('vendor')),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        return response()->json([
            'user' => new UserResource($user->load('vendor')),
        ]);
    }

    /**
     * Log out current user and invalidate JWT token.
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Refresh current JWT token.
     */
    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
