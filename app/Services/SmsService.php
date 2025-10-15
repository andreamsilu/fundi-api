<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * SMS Service - NextSMS Integration (Tanzania)
 * 
 * Handles SMS sending via NextSMS API for OTP and notifications
 * Documentation: https://messaging-service.co.tz/docs
 */
class SmsService
{
    private string $baseUrl;
    private string $authorization;
    private string $senderId;
    private bool $enabled;

    public function __construct()
    {
        $this->baseUrl = config('services.nextsms.api_url');
        $this->authorization = config('services.nextsms.authorization');
        $this->senderId = config('services.nextsms.sender_id');
        
        // Enable if credentials are configured, regardless of SMS_PROVIDER setting
        $this->enabled = !empty($this->authorization) && !empty($this->baseUrl);
    }

    /**
     * Check if SMS service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && 
               !empty($this->authorization) && 
               !empty($this->senderId);
    }

    /**
     * Send SMS message
     * 
     * @param string $phoneNumber Phone number (format: 255769289824 or 0769289824)
     * @param string $message SMS message content
     * @return array Response with success status and message
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        if (!$this->enabled) {
            Log::warning('NextSMS: Service is disabled');
            return [
                'success' => false,
                'message' => 'SMS service is disabled',
            ];
        }

        if (!$this->isConfigured()) {
            Log::error('NextSMS: Service not properly configured');
            return [
                'success' => false,
                'message' => 'SMS service not configured',
            ];
        }

        try {
            // Format phone number for NextSMS (must start with 255)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            Log::info('NextSMS: Sending SMS', [
                'to' => $formattedPhone,
                'sender_id' => $this->senderId,
                'message_length' => strlen($message),
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->authorization,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl, [
                'from' => $this->senderId,
                'to' => $formattedPhone,
                'text' => $message,
            ]);

            $statusCode = $response->status();
            $responseBody = $response->json();

            Log::info('NextSMS: Response received', [
                'status' => $statusCode,
                'response' => $responseBody,
            ]);

            if ($statusCode === 200 || $statusCode === 201) {
                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $responseBody,
                ];
            }

            return [
                'success' => false,
                'message' => $responseBody['message'] ?? 'Failed to send SMS',
                'error' => $responseBody,
            ];

        } catch (\Exception $e) {
            Log::error('NextSMS: Exception while sending SMS', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number for NextSMS API
     * Converts 0769289824 to 255769289824
     * 
     * @param string $phoneNumber Phone number in any format
     * @return string Formatted phone number (255XXXXXXXXX)
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any spaces, dashes, or special characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If starts with 0, replace with 255
        if (substr($phone, 0, 1) === '0') {
            $phone = '255' . substr($phone, 1);
        }

        // If doesn't start with 255, add it
        if (substr($phone, 0, 3) !== '255') {
            $phone = '255' . $phone;
        }

        return $phone;
    }

    /**
     * Send OTP via SMS and store in cache
     * 
     * @param string $phoneNumber Phone number to send OTP to
     * @param int $otpLength Length of OTP code (default: 6)
     * @param int $expiryMinutes OTP expiry time in minutes (default: 5)
     * @return array Response with OTP and send status
     */
    public function sendOtp(
        string $phoneNumber, 
        int $otpLength = 6, 
        int $expiryMinutes = 5
    ): array {
        // Generate OTP
        $otp = $this->generateOtp($otpLength);

        // Store OTP in cache with expiry
        $cacheKey = "otp:{$phoneNumber}";
        Cache::put($cacheKey, $otp, now()->addMinutes($expiryMinutes));

        Log::info('OTP generated and cached', [
            'phone' => $phoneNumber,
            'cache_key' => $cacheKey,
            'expiry_minutes' => $expiryMinutes,
        ]);

        // Send SMS
        $message = "Your OTP verification code is: {$otp}. Valid for {$expiryMinutes} minutes. Do not share this code.";
        $result = $this->sendSms($phoneNumber, $message);

        // Return OTP in debug mode
        if (config('app.debug')) {
            $result['otp'] = $otp;
        }

        $result['expires_in'] = $expiryMinutes * 60; // seconds

        return $result;
    }

    /**
     * Verify OTP against cached value
     * 
     * @param string $phoneNumber Phone number
     * @param string $otp OTP code to verify
     * @return bool True if OTP is valid
     */
    public function verifyOtp(string $phoneNumber, string $otp): bool
    {
        $cacheKey = "otp:{$phoneNumber}";
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp) {
            Log::warning('OTP verification failed: OTP not found or expired', [
                'phone' => $phoneNumber,
            ]);
            return false;
        }

        $isValid = $cachedOtp === $otp;

        if ($isValid) {
            // Delete OTP from cache after successful verification
            Cache::forget($cacheKey);
            Log::info('OTP verified successfully', [
                'phone' => $phoneNumber,
            ]);
        } else {
            Log::warning('OTP verification failed: Invalid OTP', [
                'phone' => $phoneNumber,
            ]);
        }

        return $isValid;
    }

    /**
     * Generate random OTP code
     * 
     * @param int $length Length of OTP
     * @return string Generated OTP
     */
    private function generateOtp(int $length = 6): string
    {
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        return (string) rand($min, $max);
    }

    /**
     * Clear OTP from cache (useful for resend functionality)
     * 
     * @param string $phoneNumber Phone number
     * @return void
     */
    public function clearOtp(string $phoneNumber): void
    {
        $cacheKey = "otp:{$phoneNumber}";
        Cache::forget($cacheKey);
        
        Log::info('OTP cleared from cache', [
            'phone' => $phoneNumber,
        ]);
    }
}

