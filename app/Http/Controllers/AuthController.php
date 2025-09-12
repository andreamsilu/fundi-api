<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user (always defaults to customer role)
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string|max:15|unique:users',
                'password' => 'required|string|min:6',
                'nida_number' => 'required|string|max:20',
                // Roles are optional and will default to ['customer']
                'roles' => 'sometimes|array',
                'roles.*' => 'string|in:customer,fundi,admin,moderator,premium_customer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Always default to customer role unless explicitly specified
            $roles = $request->get('roles', ['customer']);

            $user = User::create([
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'roles' => $roles, // Will be ['customer'] by default
                'nida_number' => $request->nida_number,
            ]);

            $token = $user->createToken(
                'auth_token',
                ['*']
            );

            // User session is now handled by Laravel Sanctum
            // No need for custom UserSession model


            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                    'roles' => $user->roles,
                    'token' => $token->plainTextToken,
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

            $token = $user->createToken(
                'auth_token',
                ['*']
            );

            // User session is now handled by Laravel Sanctum
            // No need for custom UserSession model


            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                    'roles' => $user->roles,
                    'token' => $token->plainTextToken,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Custom token validation to bypass middleware issues
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided',
                    'error' => 'UNAUTHENTICATED'
                ], 401);
            }

            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'error' => 'INVALID_TOKEN'
                ], 401);
            }

            // Delete the token
            $accessToken->delete();

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
     * Get token information
     */
    public function tokenInfo(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not found',
                    'error' => 'TOKEN_NOT_FOUND'
                ], 404);
            }

            $tokenInfo = [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'created_at' => $token->created_at->toISOString(),
                'expires_at' => $token->expires_at?->toISOString(),
                'last_used_at' => $token->last_used_at?->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Token information retrieved successfully',
                'data' => $tokenInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve token information',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving token information'
            ], 500);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function me(Request $request): JsonResponse
    {
        try {
            // Custom token validation to bypass middleware issues
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided',
                    'error' => 'UNAUTHENTICATED'
                ], 401);
            }

            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'error' => 'INVALID_TOKEN'
                ], 401);
            }

            $user = $accessToken->tokenable;

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
