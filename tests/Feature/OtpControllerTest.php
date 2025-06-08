<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\OtpService;
use App\Services\NextsmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;

class OtpControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $otpService;
    protected $nextsmsService;
    protected $baseUrl = '/api/v1/otp';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the services
        $this->otpService = Mockery::mock(OtpService::class);
        $this->nextsmsService = Mockery::mock(NextsmsService::class);
        
        $this->app->instance(OtpService::class, $this->otpService);
        $this->app->instance(NextsmsService::class, $this->nextsmsService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_send_otp_with_valid_phone_number()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';
        $reference = 'test123';

        $this->otpService
            ->shouldReceive('hasValidOtp')
            ->with($phone)
            ->once()
            ->andReturn(false);

        $this->otpService
            ->shouldReceive('generateOtp')
            ->once()
            ->andReturn($otp);

        $this->otpService
            ->shouldReceive('storeOtp')
            ->with($phone, $otp)
            ->once();

        $this->nextsmsService
            ->shouldReceive('sendOtp')
            ->once()
            ->andReturn(['success' => true, 'reference' => $reference]);

        // Act
        $response = $this->postJson($this->baseUrl . '/send', [
            'to' => $phone
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'OTP sent successfully',
                'status' => 'success',
                'reference' => $reference
            ])
            ->assertJsonStructure([
                'message',
                'expires_in',
                'reference',
                'status'
            ]);
    }

    /** @test */
    public function it_validates_phone_number_format()
    {
        // Act
        $response = $this->postJson($this->baseUrl . '/send', [
            'to' => '123456' // Invalid format
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid input'
            ])
            ->assertJsonStructure([
                'message',
                'errors' => ['to'],
                'status'
            ]);
    }

    /** @test */
    public function it_prevents_sending_new_otp_when_existing_one_is_valid()
    {
        // Arrange
        $phone = '255769289824';
        $remainingTime = 240; // 4 minutes

        $this->otpService
            ->shouldReceive('hasValidOtp')
            ->with($phone)
            ->once()
            ->andReturn(true);

        $this->otpService
            ->shouldReceive('getOtpRemainingTime')
            ->with($phone)
            ->once()
            ->andReturn($remainingTime);

        // Act
        $response = $this->postJson($this->baseUrl . '/send', [
            'to' => $phone
        ]);

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'An OTP is already active. Please wait or use resend endpoint.',
                'remaining_time' => $remainingTime,
                'status' => 'error'
            ]);
    }

    /** @test */
    public function it_can_verify_valid_otp()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';

        $this->otpService
            ->shouldReceive('hasValidOtp')
            ->with($phone)
            ->once()
            ->andReturn(true);

        $this->otpService
            ->shouldReceive('verifyOtp')
            ->with($phone, $otp)
            ->once()
            ->andReturn(true);

        $this->otpService
            ->shouldReceive('invalidateOtp')
            ->with($phone)
            ->once();

        // Act
        $response = $this->postJson($this->baseUrl . '/verify', [
            'to' => $phone,
            'otp' => $otp
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'OTP verified successfully',
                'status' => 'success'
            ]);
    }

    /** @test */
    public function it_handles_invalid_otp_verification()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';
        $attempts = 1;

        $this->otpService
            ->shouldReceive('hasValidOtp')
            ->with($phone)
            ->once()
            ->andReturn(true);

        $this->otpService
            ->shouldReceive('verifyOtp')
            ->with($phone, $otp)
            ->once()
            ->andReturn(false);

        $this->otpService
            ->shouldReceive('getVerificationAttempts')
            ->with($phone)
            ->once()
            ->andReturn($attempts);

        // Act
        $response = $this->postJson($this->baseUrl . '/verify', [
            'to' => $phone,
            'otp' => $otp
        ]);

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid OTP',
                'remaining_attempts' => 2,
                'status' => 'error'
            ]);
    }

    /** @test */
    public function it_can_resend_otp_when_previous_one_is_expired()
    {
        // Arrange
        $phone = '255769289824';
        $otp = '123456';
        $reference = 'test123';

        $this->otpService
            ->shouldReceive('hasValidOtp')
            ->with($phone)
            ->once()
            ->andReturn(false);

        $this->otpService
            ->shouldReceive('generateOtp')
            ->once()
            ->andReturn($otp);

        $this->otpService
            ->shouldReceive('storeOtp')
            ->with($phone, $otp)
            ->once();

        $this->nextsmsService
            ->shouldReceive('sendOtp')
            ->once()
            ->andReturn(['success' => true, 'reference' => $reference]);

        // Act
        $response = $this->postJson($this->baseUrl . '/resend', [
            'to' => $phone
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'New OTP sent successfully',
                'status' => 'success',
                'reference' => $reference
            ])
            ->assertJsonStructure([
                'message',
                'expires_in',
                'reference',
                'status'
            ]);
    }
} 