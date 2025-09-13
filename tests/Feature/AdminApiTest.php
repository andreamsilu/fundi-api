<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Portfolio;
use App\Models\PaymentPlan;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Admin API Tests
 * 
 * Tests all admin-related endpoints including:
 * - User management
 * - Role and permission management
 * - Job and application management
 * - Portfolio management
 * - Payment management
 * - System monitoring
 */
class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected $baseUrl = '/api/v1';
    protected $adminUser;
    protected $adminToken;
    protected $customerUser;
    protected $fundiUser;

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
        // Create admin user
        $this->adminUser = User::create([
            'phone' => '+255712345680',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_verified' => true,
        ]);
        $this->adminToken = $this->adminUser->createToken('test-token')->plainTextToken;

        // Create customer user
        $this->customerUser = User::create([
            'phone' => '+255712345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_verified' => true,
        ]);

        // Create fundi user
        $this->fundiUser = User::create([
            'phone' => '+255712345679',
            'password' => Hash::make('password123'),
            'role' => 'fundi',
            'is_verified' => true,
        ]);
    }

    /**
     * Create test data
     */
    protected function createTestData(): void
    {
        // Create test category
        Category::create([
            'name' => 'Plumbing',
            'description' => 'Plumbing services',
            'is_active' => true,
        ]);

        // Create test payment plan
        PaymentPlan::create([
            'name' => 'Basic Plan',
            'description' => 'Basic subscription plan',
            'price' => 10000,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        // Create test roles and permissions
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
        $fundiRole = Role::create(['name' => 'fundi', 'display_name' => 'Fundi']);
        $customerRole = Role::create(['name' => 'customer', 'display_name' => 'Customer']);

        $viewJobsPermission = Permission::create(['name' => 'view_jobs', 'display_name' => 'View Jobs']);
        $createJobsPermission = Permission::create(['name' => 'create_jobs', 'display_name' => 'Create Jobs']);
        $applyJobsPermission = Permission::create(['name' => 'apply_jobs', 'display_name' => 'Apply for Jobs']);

        $adminRole->permissions()->attach([$viewJobsPermission->id, $createJobsPermission->id, $applyJobsPermission->id]);
        $fundiRole->permissions()->attach([$viewJobsPermission->id, $applyJobsPermission->id]);
        $customerRole->permissions()->attach([$viewJobsPermission->id, $createJobsPermission->id]);
    }

    /**
     * Test admin get all users
     */
    public function test_admin_get_all_users(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'users' => [
                        '*' => [
                            'id',
                            'phone',
                            'role',
                            'is_verified',
                            'created_at'
                        ]
                    ],
                    'pagination'
                ]
            ]);
    }

    /**
     * Test admin get user by ID
     */
    public function test_admin_get_user_by_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/users/' . $this->customerUser->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'phone',
                    'role',
                    'is_verified',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test admin update user
     */
    public function test_admin_update_user(): void
    {
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'is_verified' => true
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->patchJson($this->baseUrl . '/admin/users/' . $this->customerUser->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User updated successfully'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->customerUser->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'is_verified' => true
        ]);
    }

    /**
     * Test admin delete user
     */
    public function test_admin_delete_user(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->deleteJson($this->baseUrl . '/admin/users/' . $this->customerUser->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $this->customerUser->id
        ]);
    }

    /**
     * Test admin get all roles
     */
    public function test_admin_get_all_roles(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'roles' => [
                        '*' => [
                            'id',
                            'name',
                            'display_name',
                            'permissions'
                        ]
                    ]
                ]
            ]);
    }

    /**
     * Test admin create role
     */
    public function test_admin_create_role(): void
    {
        $roleData = [
            'name' => 'moderator',
            'display_name' => 'Moderator',
            'permissions' => [1, 2] // Permission IDs
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->postJson($this->baseUrl . '/admin/roles', $roleData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Role created successfully'
            ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'moderator',
            'display_name' => 'Moderator'
        ]);
    }

    /**
     * Test admin update role
     */
    public function test_admin_update_role(): void
    {
        $role = Role::create([
            'name' => 'moderator',
            'display_name' => 'Moderator'
        ]);

        $updateData = [
            'display_name' => 'Senior Moderator',
            'permissions' => [1, 2, 3]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->putJson($this->baseUrl . '/admin/roles/' . $role->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Role updated successfully'
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'display_name' => 'Senior Moderator'
        ]);
    }

    /**
     * Test admin delete role
     */
    public function test_admin_delete_role(): void
    {
        $role = Role::create([
            'name' => 'moderator',
            'display_name' => 'Moderator'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->deleteJson($this->baseUrl . '/admin/roles/' . $role->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id
        ]);
    }

    /**
     * Test admin get all permissions
     */
    public function test_admin_get_all_permissions(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'permissions' => [
                        '*' => [
                            'id',
                            'name',
                            'display_name'
                        ]
                    ]
                ]
            ]);
    }

    /**
     * Test admin create permission
     */
    public function test_admin_create_permission(): void
    {
        $permissionData = [
            'name' => 'manage_users',
            'display_name' => 'Manage Users'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->postJson($this->baseUrl . '/admin/permissions', $permissionData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Permission created successfully'
            ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'manage_users',
            'display_name' => 'Manage Users'
        ]);
    }

    /**
     * Test admin get all categories
     */
    public function test_admin_get_all_categories(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'is_active',
                            'created_at'
                        ]
                    ]
                ]
            ]);
    }

    /**
     * Test admin create category
     */
    public function test_admin_create_category(): void
    {
        $categoryData = [
            'name' => 'Electrical',
            'description' => 'Electrical services',
            'is_active' => true
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->postJson($this->baseUrl . '/admin/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Category created successfully'
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Electrical',
            'description' => 'Electrical services',
            'is_active' => true
        ]);
    }

    /**
     * Test admin update category
     */
    public function test_admin_update_category(): void
    {
        $category = Category::create([
            'name' => 'Electrical',
            'description' => 'Electrical services',
            'is_active' => true
        ]);

        $updateData = [
            'name' => 'Electrical Services',
            'description' => 'Professional electrical services',
            'is_active' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->patchJson($this->baseUrl . '/admin/categories/' . $category->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category updated successfully'
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Electrical Services',
            'description' => 'Professional electrical services',
            'is_active' => false
        ]);
    }

    /**
     * Test admin delete category
     */
    public function test_admin_delete_category(): void
    {
        $category = Category::create([
            'name' => 'Electrical',
            'description' => 'Electrical services',
            'is_active' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->deleteJson($this->baseUrl . '/admin/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id
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
            'category_id' => 1,
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
     * Test admin get all job applications
     */
    public function test_admin_get_all_job_applications(): void
    {
        // Create test job and application
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => 1,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

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
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/job-applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'applications' => [
                        '*' => [
                            'id',
                            'job_id',
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
     * Test admin update job application
     */
    public function test_admin_update_job_application(): void
    {
        // Create test job and application
        $job = Job::create([
            'title' => 'Fix Leaky Faucet',
            'description' => 'Need to fix a leaky faucet in the kitchen',
            'location' => 'Dar es Salaam',
            'budget' => 25000,
            'category_id' => 1,
            'customer_id' => $this->customerUser->id,
            'status' => 'open'
        ]);

        $application = JobApplication::create([
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

        $updateData = [
            'status' => 'accepted',
            'notes' => 'Application accepted by admin'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->patchJson($this->baseUrl . '/admin/job-applications/' . $application->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Job application updated successfully'
            ]);

        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'status' => 'accepted'
        ]);
    }

    /**
     * Test admin get all portfolios
     */
    public function test_admin_get_all_portfolios(): void
    {
        // Create test portfolio
        Portfolio::create([
            'title' => 'Test Portfolio',
            'description' => 'Test portfolio description',
            'fundi_id' => $this->fundiUser->id,
            'category_id' => 1,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/portfolio');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'portfolios' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'fundi_id',
                            'category_id',
                            'status',
                            'created_at'
                        ]
                    ],
                    'pagination'
                ]
            ]);
    }

    /**
     * Test admin get payment plans
     */
    public function test_admin_get_payment_plans(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/payment-plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'payment_plans' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'price',
                            'duration_days',
                            'is_active',
                            'created_at'
                        ]
                    ]
                ]
            ]);
    }

    /**
     * Test admin create payment plan
     */
    public function test_admin_create_payment_plan(): void
    {
        $planData = [
            'name' => 'Premium Plan',
            'description' => 'Premium subscription plan',
            'price' => 25000,
            'duration_days' => 90,
            'is_active' => true
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->postJson($this->baseUrl . '/admin/payment-plans', $planData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Payment plan created successfully'
            ]);

        $this->assertDatabaseHas('payment_plans', [
            'name' => 'Premium Plan',
            'description' => 'Premium subscription plan',
            'price' => 25000,
            'duration_days' => 90,
            'is_active' => true
        ]);
    }

    /**
     * Test admin get payment statistics
     */
    public function test_admin_get_payment_statistics(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/payment-statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_revenue',
                    'monthly_revenue',
                    'active_subscriptions',
                    'payment_plans_count'
                ]
            ]);
    }

    /**
     * Test admin get active users
     */
    public function test_admin_get_active_users(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/monitor/active-users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'active_users_count',
                    'users_by_role',
                    'recent_activity'
                ]
            ]);
    }

    /**
     * Test admin get jobs summary
     */
    public function test_admin_get_jobs_summary(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/monitor/jobs-summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_jobs',
                    'jobs_by_status',
                    'recent_jobs'
                ]
            ]);
    }

    /**
     * Test admin get payments summary
     */
    public function test_admin_get_payments_summary(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/monitor/payments-summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_revenue',
                    'revenue_by_type',
                    'recent_payments'
                ]
            ]);
    }

    /**
     * Test admin get system health
     */
    public function test_admin_get_system_health(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/monitor/system-health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uptime',
                    'database_status',
                    'queue_size',
                    'storage_usage',
                    'memory_usage'
                ]
            ]);
    }

    /**
     * Test admin get API logs
     */
    public function test_admin_get_api_logs(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/monitor/api-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'logs' => [
                        '*' => [
                            'id',
                            'method',
                            'url',
                            'status_code',
                            'response_time',
                            'created_at'
                        ]
                    ],
                    'pagination'
                ]
            ]);
    }

    /**
     * Test admin get Laravel logs
     */
    public function test_admin_get_laravel_logs(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'logs' => [
                        '*' => [
                            'level',
                            'message',
                            'context',
                            'created_at'
                        ]
                    ]
                ]
            ]);
    }

    /**
     * Test admin access without admin role
     */
    public function test_admin_access_without_admin_role(): void
    {
        $customerToken = $this->customerUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $customerToken
        ])->getJson($this->baseUrl . '/admin/users');

        $response->assertStatus(403);
    }
}
