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
                'nida_number' => 'sometimes|string|max:20', // Optional during registration, will be filled in profile
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
                'nida_number' => $request->nida_number ?? 'N/A', // Provide default value if not provided
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
     * Send password reset OTP
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string|exists:users,phone',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate OTP (6 digits)
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store OTP in cache for 10 minutes
            \Cache::put('password_reset_otp_' . $request->phone_number, $otp, 600);

            // Send SMS with OTP
            $this->sendSmsOtp($request->phone_number, $otp, 'Password Reset');

            return response()->json([
                'success' => true,
                'message' => 'Password reset OTP sent successfully',
                'data' => [
                    'phone_number' => $request->phone_number,
                    'otp' => config('app.debug') ? $otp : null // Only show OTP in debug mode
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset OTP',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while sending OTP'
            ], 500);
        }
    }

    /**
     * Reset password with OTP verification
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string|exists:users,phone',
                'otp' => 'required|string|size:6',
                'new_password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify OTP
            $storedOtp = \Cache::get('password_reset_otp_' . $request->phone_number);
            
            if (!$storedOtp || $storedOtp !== $request->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 400);
            }

            // Find user and update password
            $user = User::where('phone', $request->phone_number)->first();
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Clear OTP from cache
            \Cache::forget('password_reset_otp_' . $request->phone_number);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while resetting password'
            ], 500);
        }
    }

    /**
     * Send OTP for phone verification
     */
    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string|max:15',
                'type' => 'required|string|in:registration,password_reset,phone_change',
                'user_id' => 'sometimes|integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate OTP (6 digits)
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store OTP in cache for 10 minutes
            $cacheKey = 'otp_' . $request->phone_number . '_' . $request->type;
            \Cache::put($cacheKey, $otp, 600);

            // Send SMS with OTP
            $messageType = ucfirst(str_replace('_', ' ', $request->type));
            $this->sendSmsOtp($request->phone_number, $otp, $messageType);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'data' => [
                    'phone_number' => $request->phone_number,
                    'type' => $request->type,
                    'otp' => config('app.debug') ? $otp : null // Only show OTP in debug mode
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while sending OTP'
            ], 500);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string|max:15',
                'otp' => 'required|string|size:6',
                'type' => 'required|string|in:registration,password_reset,phone_change',
                'user_id' => 'sometimes|integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify OTP
            $cacheKey = 'otp_' . $request->phone_number . '_' . $request->type;
            $storedOtp = \Cache::get($cacheKey);
            
            if (!$storedOtp || $storedOtp !== $request->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 400);
            }

            // Clear OTP from cache
            \Cache::forget($cacheKey);

            // For registration type, create user session
            if ($request->type === 'registration') {
                $user = User::where('phone', $request->phone_number)->first();
                if ($user) {
                    $token = $user->createToken('auth_token', ['*']);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'OTP verified successfully',
                        'data' => [
                            'user' => $user,
                            'token' => $token->plainTextToken
                        ]
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify OTP',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while verifying OTP'
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while changing password'
            ], 500);
        }
    }

    /**
     * Send SMS OTP using Next SMS service
     */
    private function sendSmsOtp(string $phoneNumber, string $otp, string $type): bool
    {
        try {
            $smsApiUrl = env('NEXT_SMS_API_URL', 'https://messaging-service.co.tz/api/sms/v1/text/single');
            $authorization = env('NEXT_SMS_AUTHORIZATION', 'Basic bXNpbHUyMTpwYXNzdzByZEAyMDI1');
            $senderId = env('NEXT_SMS_SENDER_ID', 'HARUSI');

            // Format phone number (ensure it starts with +255)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            // Create SMS message
            $message = "Your {$type} OTP is: {$otp}. Valid for 10 minutes. Do not share this code with anyone. - Fundi App";

            // Prepare SMS payload
            $payload = [
                'from' => $senderId,
                'to' => $formattedPhone,
                'text' => $message
            ];

            // Send SMS via cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $smsApiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: ' . $authorization
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                \Log::error('SMS cURL Error: ' . $error);
                return false;
            }

            if ($httpCode !== 200) {
                \Log::error('SMS API Error: HTTP ' . $httpCode . ' - ' . $response);
                return false;
            }

            $responseData = json_decode($response, true);
            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                \Log::info('SMS sent successfully to ' . $formattedPhone);
                return true;
            } else {
                \Log::error('SMS API Error: ' . $response);
                return false;
            }

        } catch (\Exception $e) {
            \Log::error('SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format phone number for SMS service
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If it starts with 0, replace with +255
        if (strpos($phone, '0') === 0) {
            $phone = '+255' . substr($phone, 1);
        }
        // If it doesn't start with +, add +255
        elseif (strpos($phone, '255') !== 0) {
            $phone = '+255' . $phone;
        }
        // If it starts with 255, add +
        elseif (strpos($phone, '255') === 0) {
            $phone = '+' . $phone;
        }

        return $phone;
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
