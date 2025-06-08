<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NextsmsService;
use Illuminate\Http\JsonResponse;
use App\Services\OtpService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    /**
     * @var OtpService
     */
    protected $otpService;

    /**
     * @var NextsmsService
     */
    protected $nextsmsService;

    /**
     * Default sender ID for SMS
     */
    private const DEFAULT_SENDER_ID = 'SENDOFF';

    /**
     * Constructor to inject dependencies
     *
     * @param OtpService $otpService
     * @param NextsmsService $nextsmsService
     */
    public function __construct(OtpService $otpService, NextsmsService $nextsmsService)
    {
        $this->otpService = $otpService;
        $this->nextsmsService = $nextsmsService;
    }

    /**
     * Send an OTP to the provided phone number
     *
     * @authenticated
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        try {
            // Validate phone number
            $validator = Validator::make($request->all(), [
                'to' => 'required|string|regex:/^255[0-9]{9}$/',
                'from' => 'nullable|string|max:11|default:SENDOFF'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Invalid input',
                    'errors' => $validator->errors(),
                    'status' => 'error'
                ], 422);
            }

            $data = $validator->validated();
            $phone = $data['to'];
            $senderId = $data['from'] ?? self::DEFAULT_SENDER_ID;

            // Check if there's already a valid OTP
            if ($this->otpService->hasValidOtp($phone)) {
                $remainingTime = $this->otpService->getOtpRemainingTime($phone);
                return response()->json([
                    'message' => 'An OTP is already active. Please wait or use resend endpoint.',
                    'remaining_time' => $remainingTime,
                    'status' => 'error'
                ], 400);
            }

            // Generate and store new OTP
            $otp = $this->otpService->generateOtp();
            $this->otpService->storeOtp($phone, $otp);

            // Prepare SMS payload
            $smsPayload = [
                'from' => $senderId,
                'to' => $phone,
                'text' => "Your verification code is: {$otp}. Valid for 5 minutes.",
                'reference' => Str::random(8)
            ];

            // Send OTP via Nextsms
            $this->nextsmsService->sendOtp($smsPayload);

            return response()->json([
                'message' => 'OTP sent successfully',
                'expires_in' => OtpService::DEFAULT_OTP_EXPIRY,
                'reference' => $smsPayload['reference'],
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP',
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Verify the provided OTP
     *
     * @authenticated
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'to' => 'required|string|regex:/^255[0-9]{9}$/',
                'otp' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Invalid input',
                    'errors' => $validator->errors(),
                    'status' => 'error'
                ], 422);
            }

            $data = $validator->validated();
            $phone = $data['to'];
            $otp = $data['otp'];

            // Check if OTP exists
            if (!$this->otpService->hasValidOtp($phone)) {
                return response()->json([
                    'message' => 'No valid OTP found for this phone number',
                    'status' => 'error'
                ], 400);
            }

            // Verify OTP
            if ($this->otpService->verifyOtp($phone, $otp)) {
                // Invalidate OTP after successful verification
                $this->otpService->invalidateOtp($phone);

                return response()->json([
                    'message' => 'OTP verified successfully',
                    'status' => 'success'
                ]);
            }

            // Get remaining attempts
            $attempts = $this->otpService->getVerificationAttempts($phone);
            $remainingAttempts = 3 - ($attempts ?? 0);

            return response()->json([
                'message' => 'Invalid OTP',
                'remaining_attempts' => $remainingAttempts,
                'status' => 'error'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to verify OTP',
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Resend an OTP if the previous one is expired
     *
     * @authenticated
     * @param Request $request
     * @return JsonResponse
     */
    public function resend(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'to' => 'required|string|regex:/^255[0-9]{9}$/',
                'from' => 'nullable|string|max:11|default:SENDOFF'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Invalid input',
                    'errors' => $validator->errors(),
                    'status' => 'error'
                ], 422);
            }

            $data = $validator->validated();
            $phone = $data['to'];
            $senderId = $data['from'] ?? self::DEFAULT_SENDER_ID;

            // Check if there's a valid OTP
            if ($this->otpService->hasValidOtp($phone)) {
                $remainingTime = $this->otpService->getOtpRemainingTime($phone);
                return response()->json([
                    'message' => 'Your OTP is still valid. Please wait until it expires before resending.',
                    'remaining_time' => $remainingTime,
                    'status' => 'error'
                ], 400);
            }

            // Generate and store new OTP
            $otp = $this->otpService->generateOtp();
            $this->otpService->storeOtp($phone, $otp);

            // Prepare SMS payload
            $smsPayload = [
                'from' => $senderId,
                'to' => $phone,
                'text' => "Your new verification code is: {$otp}. Valid for 5 minutes.",
                'reference' => Str::random(8)
            ];

            // Send new OTP via Nextsms
            $this->nextsmsService->sendOtp($smsPayload);

            return response()->json([
                'message' => 'New OTP sent successfully',
                'expires_in' => OtpService::DEFAULT_OTP_EXPIRY,
                'reference' => $smsPayload['reference'],
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resend OTP',
                'status' => 'error'
            ], 500);
        }
    }
} 