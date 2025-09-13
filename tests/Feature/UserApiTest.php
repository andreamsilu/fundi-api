<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FundiProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * User Management API Tests
 * 
 * Tests all user-related endpoints including:
 * - User profile management
 * - Fundi profile management
 * - User role management
 * - Admin user operations
 */
class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected $baseUrl = '/api/v1';
    protected $customerUser;
    protected $fundiUser;
    protected $adminUser;
    protected $customerToken;
    protected $fundiToken;
    protected $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestUsers();
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
     * Test get current user profile
     */
    public function test_get_current_user_profile(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->getJson($this->baseUrl . '/users/me');

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
        $response = $this->getJson($this->baseUrl . '/users/me');

        $response->assertStatus(401);
    }

    /**
     * Test update user profile
     */
    public function test_update_user_profile(): void
    {
        $profileData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'location' => 'Dar es Salaam'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->patchJson($this->baseUrl . '/users/me/profile', $profileData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->customerUser->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'location' => 'Dar es Salaam'
        ]);
    }

    /**
     * Test update fundi profile
     */
    public function test_update_fundi_profile(): void
    {
        $fundiProfileData = [
            'specialization' => 'Plumbing',
            'experience_years' => 5,
            'hourly_rate' => 10000,
            'availability' => 'available',
            'bio' => 'Experienced plumber with 5 years of experience',
            'location' => 'Dar es Salaam'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->fundiToken
        ])->patchJson($this->baseUrl . '/users/me/fundi-profile', $fundiProfileData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Fundi profile updated successfully'
            ]);

        $this->assertDatabaseHas('fundi_profiles', [
            'fundi_id' => $this->fundiUser->id,
            'specialization' => 'Plumbing',
            'experience_years' => 5,
            'hourly_rate' => 10000,
            'availability' => 'available'
        ]);
    }

    /**
     * Test update fundi profile as non-fundi user
     */
    public function test_update_fundi_profile_as_customer(): void
    {
        $fundiProfileData = [
            'specialization' => 'Plumbing',
            'experience_years' => 5
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->patchJson($this->baseUrl . '/users/me/fundi-profile', $fundiProfileData);

        $response->assertStatus(403);
    }

    /**
     * Test get fundi profile by ID
     */
    public function test_get_fundi_profile_by_id(): void
    {
        // Create fundi profile
        FundiProfile::create([
            'fundi_id' => $this->fundiUser->id,
            'specialization' => 'Plumbing',
            'experience_years' => 5,
            'hourly_rate' => 10000,
            'availability' => 'available',
            'bio' => 'Experienced plumber',
            'location' => 'Dar es Salaam'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->getJson($this->baseUrl . '/users/fundi/' . $this->fundiUser->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'specialization',
                    'experience_years',
                    'hourly_rate',
                    'availability',
                    'bio',
                    'location'
                ]
            ]);
    }

    /**
     * Test get fundi profile with invalid ID
     */
    public function test_get_fundi_profile_with_invalid_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->getJson($this->baseUrl . '/users/fundi/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Fundi profile not found'
            ]);
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
     * Test admin get all users without admin role
     */
    public function test_admin_get_all_users_without_admin_role(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->customerToken
        ])->getJson($this->baseUrl . '/admin/users');

        $response->assertStatus(403);
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
     * Test admin get user with invalid ID
     */
    public function test_admin_get_user_with_invalid_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/users/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'User not found'
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
     * Test admin get all fundi profiles
     */
    public function test_admin_get_all_fundi_profiles(): void
    {
        // Create fundi profile
        FundiProfile::create([
            'fundi_id' => $this->fundiUser->id,
            'specialization' => 'Plumbing',
            'experience_years' => 5,
            'hourly_rate' => 10000,
            'availability' => 'available',
            'bio' => 'Experienced plumber',
            'location' => 'Dar es Salaam'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson($this->baseUrl . '/admin/fundi_profiles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'fundi_profiles' => [
                        '*' => [
                            'id',
                            'fundi_id',
                            'specialization',
                            'experience_years',
                            'hourly_rate',
                            'availability',
                            'status'
                        ]
                    ],
                    'pagination'
                ]
            ]);
    }

    /**
     * Test admin verify fundi profile
     */
    public function test_admin_verify_fundi_profile(): void
    {
        // Create fundi profile
        $fundiProfile = FundiProfile::create([
            'fundi_id' => $this->fundiUser->id,
            'specialization' => 'Plumbing',
            'experience_years' => 5,
            'hourly_rate' => 10000,
            'availability' => 'available',
            'bio' => 'Experienced plumber',
            'location' => 'Dar es Salaam',
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->patchJson($this->baseUrl . '/admin/fundi_profiles/' . $fundiProfile->id . '/verify', [
            'status' => 'approved',
            'notes' => 'Profile approved after verification'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Fundi profile verification updated successfully'
            ]);

        $this->assertDatabaseHas('fundi_profiles', [
            'id' => $fundiProfile->id,
            'status' => 'approved'
        ]);
    }

    /**
     * Test admin reject fundi profile
     */
    public function test_admin_reject_fundi_profile(): void
    {
        // Create fundi profile
        $fundiProfile = FundiProfile::create([
            'fundi_id' => $this->fundiUser->id,
            'specialization' => 'Plumbing',
            'experience_years' => 5,
            'hourly_rate' => 10000,
            'availability' => 'available',
            'bio' => 'Experienced plumber',
            'location' => 'Dar es Salaam',
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->patchJson($this->baseUrl . '/admin/fundi_profiles/' . $fundiProfile->id . '/verify', [
            'status' => 'rejected',
            'notes' => 'Incomplete documentation'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Fundi profile verification updated successfully'
            ]);

        $this->assertDatabaseHas('fundi_profiles', [
            'id' => $fundiProfile->id,
            'status' => 'rejected'
        ]);
    }
}
