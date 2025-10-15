<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JWTAuthController extends Controller
{
    protected SmsService $smsService;

    /**
     * Create a new AuthController instance.
     */
    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
        // Middleware is handled in routes
    }

    /**
     * Get a JWT via given credentials.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('phone', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token'
            ], 500);
        }

        $user = auth()->user();
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => new UserResource($user)
            ]
        ]);
    }

    /**
     * Register a User.
     * 
     * Simplified registration accepting phone and password only.
     * Additional profile details can be added later via profile update.
     * User is automatically assigned 'customer' role via User model boot method.
     */
    public function register(Request $request): JsonResponse
    {
        // Validate registration data
        // Only phone and password are required for initial registration
        // Additional fields (full_name, email, nida_number) are optional
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'full_name' => 'nullable|string|between:2,100',
            'email' => 'nullable|string|email|max:100|unique:users,email',
            'nida_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create user with provided data
        $userData = [
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => 'active',
        ];

        // Add optional fields if provided
        if ($request->filled('full_name')) {
            $userData['full_name'] = $request->full_name;
        }
        if ($request->filled('email')) {
            $userData['email'] = $request->email;
        }
        if ($request->filled('nida_number')) {
            $userData['nida_number'] = $request->nida_number;
        }

        $user = User::create($userData);

        // Generate JWT token for the new user
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'User successfully registered',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => new UserResource($user)
            ]
        ], 201);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout, please try again'
            ], 500);
        }
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            
            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                ]
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token could not be refreshed'
            ], 401);
        }
    }

    /**
     * Get the authenticated User.
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            return response()->json([
                'success' => true,
                'message' => 'User profile retrieved successfully',
                'data' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user profile',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Forgot password - Send OTP
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|exists:users,phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Implement actual OTP sending via SMS
        $otp = rand(100000, 999999);
        
        // In production, send OTP via SMS service
        // For now, return success (OTP would be sent via SMS)
        
        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone number',
            'data' => config('app.debug') ? ['otp' => $otp] : []
        ]);
    }

    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|exists:users,phone',
            'otp' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Verify OTP from database/cache
        
        $user = User::where('phone', $request->phone)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    }

    /**
     * Send OTP for verification via SMS
     * Generates OTP, stores in cache, and sends via NextSMS
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Send OTP via SMS service (generates, stores in cache, and sends SMS)
        $result = $this->smsService->sendOtp(
            phoneNumber: $request->phone,
            otpLength: 6,
            expiryMinutes: 5
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully to ' . $request->phone,
                'data' => [
                    'expires_in' => $result['expires_in'],
                    // Include OTP in debug mode only
                    'otp' => $result['otp'] ?? null,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to send OTP',
        ], 500);
    }

    /**
     * Verify OTP against cached value
     * Checks OTP from cache and deletes it if valid
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify OTP using SMS service
        $isValid = $this->smsService->verifyOtp($request->phone, $request->otp);

        if ($isValid) {
            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired OTP. Please request a new code.'
        ], 422);
    }
}

