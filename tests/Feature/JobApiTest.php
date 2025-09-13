<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Job Management API Tests
 * 
 * Tests all job-related endpoints including:
 * - Job creation and management
 * - Job applications
 * - Job search and filtering
 * - Admin job operations
 */
class JobApiTest extends TestCase
{
    use RefreshDatabase;

    protected $baseUrl = '/api/v1';
    protected $customerUser;
    protected $fundiUser;
    protected $adminUser;
    protected $customerToken;
    protected $fundiToken;
    protected $adminToken;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestUsers();
        $this->createTestData();
    }

    /**
     * Create test users and data
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
     * Create test data
     */
    protected function createTestData(): void
    {
        $this->category = Category::create([
            'name' => 'Plumbing',
            'description' => 'Plumbing services',
            'is_active' => true,
        ]);
    }

    /**
     * Test get all jobs
     */
    public function test_get_all_jobs(): void
    {
        // Create test jobs
        Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->getJson($this->baseUrl . '/jobs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'jobs' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'location',
                            'budget',
                            'status',
                            'category',
                            'customer'
                        ]
                    ],
                    'pagination'
                ]
            ]);
    }

    /**
     * Test get all jobs without authentication
     */
    public function test_get_all_jobs_without_auth(): void
    {
        $response = $this->getJson($this->baseUrl . '/jobs');

        $response->assertStatus(401);
    }

    /**
     * Test create job
     */
    public function test_create_job(): void
    {
        $jobData = [
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'urgency' => 'medium',
            'preferred_date' => '2024-12-31'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->postJson($this->baseUrl . '/jobs', $jobData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Job created successfully'
            ]);

        $this->assertDatabaseHas('jobs', [
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);
    }

    /**
     * Test create job with invalid data
     */
    public function test_create_job_with_invalid_data(): void
    {
        $jobData = [
            'title' => '', // Empty title
            'description' => 'Need to fix a leaky faucet',
            'location' => 'Dar es Salaam',
            'budget' => -1000, // Negative budget
            'category_id' => 999 // Non-existent category
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->postJson($this->baseUrl . '/jobs', $jobData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'budget', 'category_id']);
    }

    /**
     * Test create job as fundi (should fail)
     */
    public function test_create_job_as_fundi(): void
    {
        $jobData = [
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->fundiToken
        ])->postJson($this->baseUrl . '/jobs', $jobData);

        $response->assertStatus(403);
    }

    /**
     * Test get job by ID
     */
    public function test_get_job_by_id(): void
    {
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->getJson($this->baseUrl . '/jobs/' . $job->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'location',
                    'budget',
                    'status',
                    'category',
                    'customer',
                    'created_at'
                ]
            ]);
    }

    /**
     * Test get job with invalid ID
     */
    public function test_get_job_with_invalid_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->getJson($this->baseUrl . '/jobs/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Job not found'
            ]);
    }

    /**
     * Test update job
     */
    public function test_update_job(): void
    {
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        $updateData = [
            'title' => 'Fix Leaky Faucet - Updated',
            'description' => 'Updated description',
            'budget' => 30000
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->patchJson($this->baseUrl . '/jobs/' . $job->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Job updated successfully'
            ]);

        $this->assertDatabaseHas('jobs', [
            'id' => $job->id,
            'title' => 'Fix Leaky Faucet - Updated',
            'description' => 'Updated description',
            'budget' => 30000
        ]);
    }

    /**
     * Test delete job
     */
    public function test_delete_job(): void
    {
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->deleteJson($this->baseUrl . '/jobs/' . $job->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Job deleted successfully'
            ]);

        $this->assertDatabaseMissing('jobs', [
            'id' => $job->id
        ]);
    }

    /**
     * Test apply for job
     */
    public function test_apply_for_job(): void
    {
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        $applicationData = [
            'requirements' => 'Need 2 helpers, 3 days work',
            'budget_breakdown' => [
                'materials' => 20000,
                'labor' => 15000,
                'transport' => 5000
            ],
            'estimated_time' => 3,
            'message' => 'I can complete this job efficiently'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->fundiToken
        ])->postJson($this->baseUrl . '/jobs/' . $job->id . '/apply', $applicationData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Application submitted successfully'
            ]);

        $this->assertDatabaseHas('job_applications', [
            'job_id' => $job->id,
            'fundi_id' => $this->fundiUser->id,
            'status' => 'pending'
        ]);
    }

    /**
     * Test apply for job as customer (should fail)
     */
    public function test_apply_for_job_as_customer(): void
    {
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        $applicationData = [
            'requirements' => 'Need 2 helpers, 3 days work',
            'budget_breakdown' => [
                'materials' => 20000,
                'labor' => 15000,
                'transport' => 5000
            ],
            'estimated_time' => 3
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->postJson($this->baseUrl . '/jobs/' . $job->id . '/apply', $applicationData);

        $response->assertStatus(403);
    }

    /**
     * Test get job applications
     */
    public function test_get_job_applications(): void
    {
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        // Create job application
        JobApplication::create([
            'job_id' => $job->id,
            'fundi_id' => $this->fundiUser->id,
            'requirements' => 'Need 2 helpers, 3 days work',
            'budget_breakdown' => json_encode([
                'materials' => 20000,
                'labor' => 15000,
                'transport' => 5000
            ]),
            'estimated_time' => 3,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->getJson($this->baseUrl . '/jobs/' . $job->id . '/applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'applications' => [
                        '*' => [
                            'id',
                            'fundi_id',
                            'requirements',
                            'budget_breakdown',
                            'estimated_time',
                            'status',
                            'created_at'
                        ]
                    ],
                    'pagination'
                ]
            ]);
    }

    /**
     * Test get my applications
     */
    public function test_get_my_applications(): void
    {
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        // Create job application
        JobApplication::create([
            'job_id' => $job->id,
            'fundi_id' => $this->fundiUser->id,
            'requirements' => 'Need 2 helpers, 3 days work',
            'budget_breakdown' => json_encode([
                'materials' => 20000,
                'labor' => 15000,
                'transport' => 5000
            ]),
            'estimated_time' => 3,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->fundiToken
        ])->getJson($this->baseUrl . '/job-applications/my-applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'applications' => [
                        '*' => [
                            'id',
                            'job_id',
                            'requirements',
                            'budget_breakdown',
                            'estimated_time',
                            'status',
                            'job' => [
                                'id',
                                'title',
                                'description',
                                'location',
                                'budget'
                            ]
                        ]
                    ],
                    'pagination'
                ]
            ]);
    }

    /**
     * Test admin get all jobs
     */
    public function test_admin_get_all_jobs(): void
    {
        // Create test job
        Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/jobs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'jobs' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'location',
                            'budget',
                            'status',
                            'category',
                            'customer',
                            'created_at'
                        ]
                    ],
                    'pagination'
                ]
            ]);
    }

    /**
     * Test admin update job
     */
    public function test_admin_update_job(): void
    {
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        $updateData = [
            'status' => 'cancelled',
            'notes' => 'Job cancelled by admin'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->patchJson($this->baseUrl . '/admin/jobs/' . $job->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Job updated successfully'
            ]);

        $this->assertDatabaseHas('jobs', [
            'id' => $job->id,
            'status' => 'cancelled'
        ]);
    }

    /**
     * Test admin delete job
     */
    public function test_admin_delete_job(): void
    {
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => $this->category->id,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->deleteJson($this->baseUrl . '/admin/jobs/' . $job->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Job deleted successfully'
            ]);

        $this->assertDatabaseMissing('jobs', [
            'id' => $job->id
        ]);
    }
}
