<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\LoginResponseResource;
use App\Http\Resources\Auth\LogoutResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): LoginResponseResource|JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $tokenName = $request->userAgent() ?: 'api-token';
        $token = $user->createToken($tokenName)->plainTextToken;

        return new LoginResponseResource([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'role' => $user->role,
        ]);
    }

    public function logout(Request $request): LogoutResponseResource
    {
        $request->user()->currentAccessToken()->delete();

        return new LogoutResponseResource([
            'message' => 'Logged out successfully.',
        ]);
    }
}
