<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otpService = new OtpService();
        Cache::flush();
    }

    /** @test */
    public function it_generates_otp_of_correct_length()
    {
        // Act
        $otp = $this->otpService->generateOtp();

        // Assert
        $this->assertEquals(6, strlen($otp));
        $this->assertIsNumeric($otp);
    }

    /** @test */
    public function it_stores_otp_with_expiry()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';

        // Act
        $result = $this->otpService->storeOtp($phone, $otp);

        // Assert
        $this->assertTrue($result);
        $this->assertTrue(Cache::has($this->otpService->getCacheKey($phone)));
        
        $storedData = Cache::get($this->otpService->getCacheKey($phone));
        $this->assertEquals($otp, $storedData['otp']);
        $this->assertEquals(0, $storedData['attempts']);
    }

    /** @test */
    public function it_verifies_valid_otp()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';
        $this->otpService->storeOtp($phone, $otp);

        // Act
        $result = $this->otpService->verifyOtp($phone, $otp);

        // Assert
        $this->assertTrue($result);
        
        // Check that attempts were incremented
        $storedData = Cache::get($this->otpService->getCacheKey($phone));
        $this->assertEquals(1, $storedData['attempts']);
    }

    /** @test */
    public function it_rejects_invalid_otp()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';
        $this->otpService->storeOtp($phone, $otp);

        // Act
        $result = $this->otpService->verifyOtp($phone, '000000');

        // Assert
        $this->assertFalse($result);
        
        // Check that attempts were incremented
        $storedData = Cache::get($this->otpService->getCacheKey($phone));
        $this->assertEquals(1, $storedData['attempts']);
    }

    /** @test */
    public function it_invalidates_otp_after_max_attempts()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';
        $this->otpService->storeOtp($phone, $otp);

        // Act - Try 3 times with wrong OTP
        $this->otpService->verifyOtp($phone, '000000');
        $this->otpService->verifyOtp($phone, '000000');
        $result = $this->otpService->verifyOtp($phone, '000000');

        // Assert
        $this->assertFalse($result);
        $this->assertFalse($this->otpService->hasValidOtp($phone));
    }

    /** @test */
    public function it_checks_otp_validity()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';

        // Act & Assert - Before storing
        $this->assertFalse($this->otpService->hasValidOtp($phone));

        // Act & Assert - After storing
        $this->otpService->storeOtp($phone, $otp);
        $this->assertTrue($this->otpService->hasValidOtp($phone));

        // Act & Assert - After invalidation
        $this->otpService->invalidateOtp($phone);
        $this->assertFalse($this->otpService->hasValidOtp($phone));
    }

    /** @test */
    public function it_returns_remaining_time_for_valid_otp()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';
        $this->otpService->storeOtp($phone, $otp);

        // Act
        $remainingTime = $this->otpService->getOtpRemainingTime($phone);

        // Assert
        $this->assertIsInt($remainingTime);
        $this->assertGreaterThan(0, $remainingTime);
        $this->assertLessThanOrEqual(300, $remainingTime); // Should not exceed 5 minutes
    }

    /** @test */
    public function it_returns_null_for_remaining_time_of_invalid_otp()
    {
        // Arrange
        $phone = '255769289824';

        // Act
        $remainingTime = $this->otpService->getOtpRemainingTime($phone);

        // Assert
        $this->assertNull($remainingTime);
    }

    /** @test */
    public function it_tracks_verification_attempts()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';
        $this->otpService->storeOtp($phone, $otp);

        // Act & Assert - Initial attempts
        $this->assertEquals(0, $this->otpService->getVerificationAttempts($phone));

        // Act & Assert - After one attempt
        $this->otpService->verifyOtp($phone, '000000');
        $this->assertEquals(1, $this->otpService->getVerificationAttempts($phone));

        // Act & Assert - After successful verification
        $this->otpService->verifyOtp($phone, $otp);
        $this->assertNull($this->otpService->getVerificationAttempts($phone)); // Should be null as OTP is invalidated
    }
} 