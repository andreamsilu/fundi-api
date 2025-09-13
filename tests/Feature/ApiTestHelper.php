<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Portfolio;
use App\Models\PaymentPlan;
use Illuminate\Support\Facades\Hash;

/**
 * API Test Helper Class
 * 
 * Provides common utilities and methods for testing API endpoints
 * following MVC pattern and Laravel best practices
 */
class ApiTestHelper
{
    use RefreshDatabase, WithFaker;

    protected $baseUrl = '/api/v1';
    protected $customerToken;
    protected $fundiToken;
    protected $adminToken;
    protected $customerUser;
    protected $fundiUser;
    protected $adminUser;

    /**
     * Setup test data and authentication tokens
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createTestUsers();
        $this->createTestData();
    }

    /**
     * Create test users with different roles
     */
    protected function createTestUsers(): void
    {
        // Create customer user
        $this->customerUser = User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_verified' => true,
        ]);
        $this->customerToken = $this->customerUser->createToken('test-token')->plainTextToken;

        // Create fundi user
        $this->fundiUser = User::create([
            'phone' => '+255712345679',
            'password' => Hash::make('password123'),
            'role' => 'fundi',
            'is_verified' => true,
        ]);
        $this->fundiToken = $this->fundiUser->createToken('test-token')->plainTextToken;

        // Create admin user
        $this->adminUser = User::create([
            'phone' => '+255712345680',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_verified' => true,
        ]);
        $this->adminToken = $this->adminUser->createToken('test-token')->plainTextToken;
    }

    /**
     * Create test data for various endpoints
     */
    protected function createTestData(): void
    {
        // Create test categories
        Category::create([
            'name' => 'Plumbing',
            'description' => 'Plumbing services',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Electrical',
            'description' => 'Electrical services',
            'is_active' => true,
        ]);

        // Create test payment plans
        PaymentPlan::create([
            'name' => 'Basic Plan',
            'description' => 'Basic subscription plan',
            'price' => 10000,
            'duration_days' => 30,
            'is_active' => true,
        ]);
    }

    /**
     * Make authenticated request
     */
    protected function authenticatedRequest(string $method, string $endpoint, array $data = [], string $token = null): \Illuminate\Testing\TestResponse
    {
        $headers = [];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $this->json($method, $this->baseUrl . $endpoint, $data, $headers);
    }

    /**
     * Get customer token
     */
    protected function getCustomerToken(): string
    {
        return $this->customerToken;
    }

    /**
     * Get fundi token
     */
    protected function getFundiToken(): string
    {
        return $this->fundiToken;
    }

    /**
     * Get admin token
     */
    protected function getAdminToken(): string
    {
        return $this->adminToken;
    }

    /**
     * Create test job
     */
    protected function createTestJob(): Job
    {
        return Job::create([
            'title' => 'Test Job',
            'description' => 'Test job description',
            'location' => 'Dar es Salaam',
            'budget' => 50000,
            'category_id' => 1,
            'customer_id' => $this->customerUser->id,
            'status' => 'open',
        ]);
    }

    /**
     * Create test portfolio
     */
    protected function createTestPortfolio(): Portfolio
    {
        return Portfolio::create([
            'title' => 'Test Portfolio',
            'description' => 'Test portfolio description',
            'fundi_id' => $this->fundiUser->id,
            'category_id' => 1,
            'status' => 'pending',
        ]);
    }

    /**
     * Assert successful response
     */
    protected function assertSuccessResponse(\Illuminate\Testing\TestResponse $response, int $expectedStatus = 200): void
    {
        $response->assertStatus($expectedStatus);
        $response->assertJsonStructure([
            'success',
            'message',
            'data'
        ]);
    }

    /**
     * Assert error response
     */
    protected function assertErrorResponse(\Illuminate\Testing\TestResponse $response, int $expectedStatus = 400): void
    {
        $response->assertStatus($expectedStatus);
        $response->assertJsonStructure([
            'success',
            'message'
        ]);
        $this->assertFalse($response->json('success'));
    }

    /**
     * Assert validation error response
     */
    protected function assertValidationError(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors'
        ]);
    }

    /**
     * Assert unauthorized response
     */
    protected function assertUnauthorized(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertStatus(401);
    }

    /**
     * Assert forbidden response
     */
    protected function assertForbidden(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertStatus(403);
    }
}
