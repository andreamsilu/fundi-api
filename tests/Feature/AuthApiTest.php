<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Authentication API Tests
 * 
 * Tests all authentication-related endpoints including:
 * - User registration
 * - User login
 * - Password reset
 * - OTP verification
 * - User logout
 * - Profile management
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected $baseUrl = '/api/v1';

    /**
     * Test user registration with valid data
     */
    public function test_user_registration_with_valid_data(): void
    {
        $userData = [
            'phone' => '+255712345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer'
        ];

        $response = $this->postJson($this->baseUrl . '/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'phone',
                    'role',
                    'token'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => '+255712345678',
            'role' => 'customer'
        ]);
    }

    /**
     * Test user registration with invalid data
     */
    public function test_user_registration_with_invalid_data(): void
    {
        $userData = [
            'phone' => 'invalid-phone',
            'password' => '123', // Too short
            'role' => 'invalid-role'
        ];

        $response = $this->postJson($this->baseUrl . '/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'phone',
                    'password',
                    'role'
                ]
            ]);
    }

    /**
     * Test user registration with duplicate phone
     */
    public function test_user_registration_with_duplicate_phone(): void
    {
        // Create existing user
        User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('password123'),
            'role' => 'customer'
        ]);

        $userData = [
            'phone' => '+255712345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'fundi'
        ];

        $response = $this->postJson($this->baseUrl . '/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /**
     * Test user login with valid credentials
     */
    public function test_user_login_with_valid_credentials(): void
    {
        // Create user
        $user = User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_verified' => true
        ]);

        $loginData = [
            'phone' => '+255712345678',
            'password' => 'password123'
        ];

        $response = $this->postJson($this->baseUrl . '/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'phone',
                    'role',
                    'token'
                ]
            ]);
    }

    /**
     * Test user login with invalid credentials
     */
    public function test_user_login_with_invalid_credentials(): void
    {
        $loginData = [
            'phone' => '+255712345678',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson($this->baseUrl . '/auth/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
    }

    /**
     * Test user login with unverified account
     */
    public function test_user_login_with_unverified_account(): void
    {
        // Create unverified user
        User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_verified' => false
        ]);

        $loginData = [
            'phone' => '+255712345678',
            'password' => 'password123'
        ];

        $response = $this->postJson($this->baseUrl . '/auth/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Account not verified'
            ]);
    }

    /**
     * Test forgot password with valid phone
     */
    public function test_forgot_password_with_valid_phone(): void
    {
        // Create user
        User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_verified' => true
        ]);

        $response = $this->postJson($this->baseUrl . '/auth/forgot-password', [
            'phone' => '+255712345678'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent successfully'
            ]);
    }

    /**
     * Test forgot password with invalid phone
     */
    public function test_forgot_password_with_invalid_phone(): void
    {
        $response = $this->postJson($this->baseUrl . '/auth/forgot-password', [
            'phone' => '+255999999999'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'User not found'
            ]);
    }

    /**
     * Test OTP verification
     */
    public function test_otp_verification(): void
    {
        // Create user with OTP
        $user = User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_verified' => false,
            'otp' => '123456',
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        $response = $this->postJson($this->baseUrl . '/auth/verify-otp', [
            'phone' => '+255712345678',
            'otp' => '123456'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP verified successfully'
            ]);

        // Check user is verified
        $this->assertDatabaseHas('users', [
            'phone' => '+255712345678',
            'is_verified' => true
        ]);
    }

    /**
     * Test OTP verification with invalid OTP
     */
    public function test_otp_verification_with_invalid_otp(): void
    {
        // Create user with OTP
        User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_verified' => false,
            'otp' => '123456',
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        $response = $this->postJson($this->baseUrl . '/auth/verify-otp', [
            'phone' => '+255712345678',
            'otp' => '999999'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ]);
    }

    /**
     * Test password reset with valid OTP
     */
    public function test_password_reset_with_valid_otp(): void
    {
        // Create user with OTP
        $user = User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('oldpassword'),
            'role' => 'customer',
            'is_verified' => true,
            'otp' => '123456',
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        $response = $this->postJson($this->baseUrl . '/auth/reset-password', [
            'phone' => '+255712345678',
            'otp' => '123456',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * Test get current user profile
     */
    public function test_get_current_user_profile(): void
    {
        $user = User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_verified' => true
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson($this->baseUrl . '/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'phone',
                    'role',
                    'is_verified'
                ]
            ]);
    }

    /**
     * Test get current user profile without authentication
     */
    public function test_get_current_user_profile_without_auth(): void
    {
        $response = $this->getJson($this->baseUrl . '/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test change password
     */
    public function test_change_password(): void
    {
        $user = User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('oldpassword'),
            'role' => 'customer',
            'is_verified' => true
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson($this->baseUrl . '/auth/change-password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * Test change password with wrong current password
     */
    public function test_change_password_with_wrong_current_password(): void
    {
        $user = User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('oldpassword'),
            'role' => 'customer',
            'is_verified' => true
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson($this->baseUrl . '/auth/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Current password is incorrect'
            ]);
    }

    /**
     * Test user logout
     */
    public function test_user_logout(): void
    {
        $user = User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_verified' => true
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson($this->baseUrl . '/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
    }

    /**
     * Test send OTP
     */
    public function test_send_otp(): void
    {
        $response = $this->postJson($this->baseUrl . '/auth/send-otp', [
            'phone' => '+255712345678',
            'type' => 'registration'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent successfully'
            ]);
    }
}
