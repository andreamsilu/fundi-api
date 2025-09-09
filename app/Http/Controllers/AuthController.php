<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:customer,fundi,admin',
            'nida_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'nida_number' => $request->nida_number,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Create user session
        UserSession::create([
            'user_id' => $user->id,
            'device_info' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
            'token' => $token,
            'expired_at' => now()->addDays(30),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'role' => $user->role,
                'token' => $token
            ]
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Create user session
        UserSession::create([
            'user_id' => $user->id,
            'device_info' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
            'token' => $token,
            'expired_at' => now()->addDays(30),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'role' => $user->role,
                'token' => $token
            ]
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        
        // Update user session
        UserSession::where('token', $token->token)
            ->update(['logout_at' => now()]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get authenticated user profile
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('fundiProfile');

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
}
