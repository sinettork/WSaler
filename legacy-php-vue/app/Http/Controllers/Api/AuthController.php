<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('spa');

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'description' => 'User logged in',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities ?? [],
        ]);
    }

    public function logout(Request $request): Response
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        ActivityLog::create([
            'user_id' => $user?->id,
            'action' => 'logout',
            'description' => 'User logged out',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Cashier,
        ]);

        // Link the user to the Cashier Role so they actually receive permissions.
        // Without this the new account has zero permissions on every endpoint.
        $user->assignRole(UserRole::Cashier->value);

        $token = $user->createToken('spa');

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'registered',
            'description' => 'New user registered',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities ?? [],
        ], 201);
    }
}
