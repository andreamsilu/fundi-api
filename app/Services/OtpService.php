<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Default OTP expiry time in seconds (5 minutes)
     */
    private const DEFAULT_OTP_EXPIRY = 300;

    /**
     * Default OTP length
     */
    private const DEFAULT_OTP_LENGTH = 6;

    /**
     * Generate a new OTP
     *
     * @param int $length Length of the OTP (default: 6)
     * @return string
     */
    public function generateOtp(int $length = self::DEFAULT_OTP_LENGTH): string
    {
        return str_pad((string) random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Store OTP in cache with expiry
     *
     * @param string $phone Phone number
     * @param string $otp The OTP to store
     * @param int $expiry Expiry time in seconds
     * @return bool
     */
    public function storeOtp(string $phone, string $otp, int $expiry = self::DEFAULT_OTP_EXPIRY): bool
    {
        $key = $this->getCacheKey($phone);
        return Cache::put($key, [
            'otp' => $otp,
            'attempts' => 0,
            'created_at' => now()->timestamp
        ], $expiry);
    }

    /**
     * Verify if the provided OTP is valid
     *
     * @param string $phone Phone number
     * @param string $otp OTP to verify
     * @return bool
     */
    public function verifyOtp(string $phone, string $otp): bool
    {
        $key = $this->getCacheKey($phone);
        $storedData = Cache::get($key);

        if (!$storedData) {
            return false;
        }

        // Increment attempts
        $storedData['attempts']++;
        Cache::put($key, $storedData, self::DEFAULT_OTP_EXPIRY);

        // Check if too many attempts
        if ($storedData['attempts'] > 3) {
            Cache::forget($key);
            return false;
        }

        return hash_equals($storedData['otp'], $otp);
    }

    /**
     * Check if OTP exists and is not expired
     *
     * @param string $phone Phone number
     * @return bool
     */
    public function hasValidOtp(string $phone): bool
    {
        $key = $this->getCacheKey($phone);
        if (!Cache::has($key)) {
            return false;
        }

        $storedData = Cache::get($key);
        if (!isset($storedData['created_at'])) {
            return false;
        }

        // For array driver, check expiry manually
        if (config('cache.default') === 'array') {
            $elapsed = now()->timestamp - $storedData['created_at'];
            return $elapsed < self::DEFAULT_OTP_EXPIRY;
        }

        return true;
    }

    /**
     * Get remaining time for OTP expiry
     *
     * @param string $phone Phone number
     * @return int|null Time remaining in seconds, null if OTP doesn't exist
     */
    public function getOtpRemainingTime(string $phone): ?int
    {
        $key = $this->getCacheKey($phone);
        if (!Cache::has($key)) {
            return null;
        }

        $storedData = Cache::get($key);
        if (!isset($storedData['created_at'])) {
            return null;
        }

        // For array driver, calculate remaining time manually
        if (config('cache.default') === 'array') {
            $elapsed = now()->timestamp - $storedData['created_at'];
            $remaining = self::DEFAULT_OTP_EXPIRY - $elapsed;
            return $remaining > 0 ? $remaining : 0;
        }

        // For other drivers, use the cache TTL
        $ttl = Cache::ttl($key);
        return $ttl > 0 ? $ttl : 0;
    }

    /**
     * Invalidate OTP for a phone number
     *
     * @param string $phone Phone number
     * @return bool
     */
    public function invalidateOtp(string $phone): bool
    {
        return Cache::forget($this->getCacheKey($phone));
    }

    /**
     * Get the number of verification attempts for an OTP
     *
     * @param string $phone Phone number
     * @return int|null Number of attempts, null if OTP doesn't exist
     */
    public function getVerificationAttempts(string $phone): ?int
    {
        $key = $this->getCacheKey($phone);
        $storedData = Cache::get($key);
        
        return $storedData ? $storedData['attempts'] : null;
    }

    /**
     * Generate a unique cache key for OTP storage
     *
     * @param string $phone Phone number
     * @return string
     */
    private function getCacheKey(string $phone): string
    {
        return 'otp_' . Str::slug($phone);
    }
} 