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
        try {
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

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during registration'
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        try {
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

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during login'
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $token = $request->user()->currentAccessToken();
            
            // Update user session
            UserSession::where('token', $token->token)
                ->update(['logout_at' => now()]);

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during logout'
            ], 500);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load('fundiProfile');

            return response()->json([
                'success' => true,
                'message' => 'User profile retrieved successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user profile',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving profile'
            ], 500);
        }
    }
}
