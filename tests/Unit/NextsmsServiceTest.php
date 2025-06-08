<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NextsmsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class NextsmsServiceTest extends TestCase
{
    protected $nextsmsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nextsmsService = new NextsmsService();
        
        // Set up test environment variables
        Config::set('services.nextsms.username', 'test_username');
        Config::set('services.nextsms.password', 'test_password');
        Config::set('services.nextsms.sender_id', 'SENDOFF');
        Config::set('services.nextsms.base_url', 'https://test-api.example.com');
    }

    /** @test */
    public function it_sends_otp_successfully()
    {
        // Arrange
        Http::fake([
            'test-api.example.com/*' => Http::response([
                'status' => 'success',
                'message' => 'Message sent successfully'
            ], 200)
        ]);

        $payload = [
            'from' => 'SENDOFF',
            'to' => '255769289824',
            'text' => 'Your verification code is: 123456. Valid for 5 minutes.',
            'reference' => 'test123'
        ];

        // Act
        $result = $this->nextsmsService->sendOtp($payload);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('test123', $result['reference']);

        Http::assertSent(function ($request) use ($payload) {
            return $request->url() === 'https://test-api.example.com/text/single' &&
                   $request->hasHeader('Authorization', 'Basic ' . base64_encode('test_username:test_password')) &&
                   $request['from'] === $payload['from'] &&
                   $request['to'] === $payload['to'] &&
                   $request['text'] === $payload['text'] &&
                   $request['reference'] === $payload['reference'];
        });
    }

    /** @test */
    public function it_handles_api_error_response()
    {
        // Arrange
        Http::fake([
            'test-api.example.com/*' => Http::response([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401)
        ]);

        $payload = [
            'from' => 'SENDOFF',
            'to' => '255769289824',
            'text' => 'Your verification code is: 123456. Valid for 5 minutes.',
            'reference' => 'test123'
        ];

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to send SMS: Invalid credentials');

        // Act
        $this->nextsmsService->sendOtp($payload);
    }

    /** @test */
    public function it_validates_required_environment_variables()
    {
        // Arrange
        Config::set('services.nextsms.username', '');
        Config::set('services.nextsms.password', '');
        Config::set('services.nextsms.sender_id', '');

        $payload = [
            'from' => 'SENDOFF',
            'to' => '255769289824',
            'text' => 'Your verification code is: 123456. Valid for 5 minutes.',
            'reference' => 'test123'
        ];

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required environment variables: NEXTSMS_USERNAME, NEXTSMS_PASSWORD, NEXTSMS_SENDER_ID');

        // Act
        $this->nextsmsService->sendOtp($payload);
    }

    /** @test */
    public function it_uses_default_sender_id_when_not_provided()
    {
        // Arrange
        Http::fake([
            'test-api.example.com/*' => Http::response([
                'status' => 'success',
                'message' => 'Message sent successfully'
            ], 200)
        ]);

        $payload = [
            'to' => '255769289824',
            'text' => 'Your verification code is: 123456. Valid for 5 minutes.',
            'reference' => 'test123'
        ];

        // Act
        $result = $this->nextsmsService->sendOtp($payload);

        // Assert
        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return $request['from'] === 'SENDOFF';
        });
    }

    /** @test */
    public function it_handles_network_errors()
    {
        // Arrange
        Http::fake([
            'test-api.example.com/*' => Http::throw(new \Exception('Network error'))
        ]);

        $payload = [
            'from' => 'SENDOFF',
            'to' => '255769289824',
            'text' => 'Your verification code is: 123456. Valid for 5 minutes.',
            'reference' => 'test123'
        ];

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to send SMS: Network error');

        // Act
        $this->nextsmsService->sendOtp($payload);
    }
} 