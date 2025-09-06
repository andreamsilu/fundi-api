<?php

namespace Tests\Feature;

use App\Models\FundiCredits;
use App\Models\FundiSubscription;
use App\Models\Job;
use App\Models\JobApplicationFee;
use App\Models\PremiumJobBooster;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MonetizationIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $fundi;
    protected User $customer;
    protected SubscriptionTier $freeTier;
    protected Job $job;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->fundi = User::factory()->create(['role' => 'fundi']);
        $this->customer = User::factory()->create(['role' => 'customer']);
        
        // Create subscription tier
        $this->freeTier = SubscriptionTier::create([
            'name' => 'Free',
            'slug' => 'free',
            'monthly_price_tzs' => 0,
            'included_job_applications' => 5,
            'features' => ['basic_support' => true],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        // Create test job
        $this->job = Job::factory()->create([
            'user_id' => $this->customer->id,
            'budget_max' => 50000,
            'business_model' => 'c2c',
            'status' => 'open'
        ]);
    }

    /** @test */
    public function job_listing_includes_monetization_information()
    {
        $response = $this->getJson('/api/v1/jobs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'application_fee',
                        'boost_status',
                        'boost_type',
                        'is_featured'
                    ]
                ]
            ]);

        $jobData = $response->json('data')[0];
        $this->assertArrayHasKey('application_fee', $jobData);
        $this->assertArrayHasKey('boost_status', $jobData);
        $this->assertArrayHasKey('boost_type', $jobData);
    }

    /** @test */
    public function job_detail_includes_monetization_information()
    {
        $response = $this->getJson("/api/v1/jobs/{$this->job->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'application_fee',
                'boost_status',
                'boost_type',
                'is_featured'
            ]);

        $jobData = $response->json();
        $this->assertArrayHasKey('application_fee', $jobData);
        $this->assertArrayHasKey('boost_status', $jobData);
        $this->assertArrayHasKey('boost_type', $jobData);
    }

    /** @test */
    public function fundi_can_check_application_eligibility()
    {
        $response = $this->actingAs($this->fundi)
            ->getJson("/api/v1/jobs/{$this->job->id}/application/eligibility");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'can_apply',
                    'reason',
                    'application_fee',
                    'payment_type',
                    'required_payment'
                ]
            ]);
    }

    /** @test */
    public function fundi_with_subscription_can_apply_to_job()
    {
        // Create active subscription
        FundiSubscription::create([
            'user_id' => $this->fundi->id,
            'subscription_tier_id' => $this->freeTier->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'remaining_applications' => 5,
            'last_reset_at' => now(),
        ]);

        $response = $this->actingAs($this->fundi)
            ->postJson("/api/v1/jobs/{$this->job->id}/apply", [
                'message' => 'I can help with this job'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'booking',
                    'application_fee'
                ]
            ]);

        // Verify application fee was created
        $this->assertDatabaseHas('job_application_fees', [
            'job_id' => $this->job->id,
            'fundi_id' => $this->fundi->id,
            'payment_type' => 'subscription',
            'status' => 'paid'
        ]);

        // Verify booking was created
        $this->assertDatabaseHas('bookings', [
            'job_id' => $this->job->id,
            'fundi_id' => $this->fundi->id,
            'customer_id' => $this->customer->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function fundi_with_credits_can_apply_to_job()
    {
        // Create credits
        FundiCredits::create([
            'user_id' => $this->fundi->id,
            'balance' => 5000,
            'total_purchased' => 5000,
            'total_used' => 0,
        ]);

        $response = $this->actingAs($this->fundi)
            ->postJson("/api/v1/jobs/{$this->job->id}/apply", [
                'message' => 'I can help with this job'
            ]);

        $response->assertStatus(200);

        // Verify application fee was created
        $this->assertDatabaseHas('job_application_fees', [
            'job_id' => $this->job->id,
            'fundi_id' => $this->fundi->id,
            'payment_type' => 'credits',
            'status' => 'paid'
        ]);
    }

    /** @test */
    public function fundi_without_payment_cannot_apply_to_job()
    {
        $response = $this->actingAs($this->fundi)
            ->postJson("/api/v1/jobs/{$this->job->id}/apply", [
                'message' => 'I can help with this job'
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient subscription applications and credits'
            ]);
    }

    /** @test */
    public function customer_can_boost_job()
    {
        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/jobs/{$this->job->id}/boost", [
                'boost_type' => 'featured'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'payment',
                'booster'
            ]);

        // Verify booster was created
        $this->assertDatabaseHas('premium_job_boosters', [
            'job_id' => $this->job->id,
            'user_id' => $this->customer->id,
            'boost_type' => 'featured',
            'status' => 'active'
        ]);

        // Verify job is now featured
        $this->assertTrue($this->job->fresh()->is_featured);
    }

    /** @test */
    public function job_boost_fee_calculation_is_correct()
    {
        $response = $this->actingAs($this->customer)
            ->getJson("/api/v1/jobs/{$this->job->id}/boost/fee?boost_type=featured");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'boost_fee',
                    'business_model',
                    'boost_type',
                    'currency'
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(500, $data['boost_fee']); // C2C boost fee
        $this->assertEquals('c2c', $data['business_model']);
    }

    /** @test */
    public function subscription_management_works_correctly()
    {
        // Get subscription tiers
        $response = $this->actingAs($this->fundi)
            ->getJson('/api/v1/subscriptions/tiers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'monthly_price_tzs',
                        'included_job_applications',
                        'features'
                    ]
                ]
            ]);

        // Subscribe to tier
        $response = $this->actingAs($this->fundi)
            ->postJson('/api/v1/subscriptions/subscribe', [
                'tier_id' => $this->freeTier->id
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'payment',
                'subscription'
            ]);
    }

    /** @test */
    public function credit_management_works_correctly()
    {
        // Get credit balance (should be 0 initially)
        $response = $this->actingAs($this->fundi)
            ->getJson('/api/v1/credits/balance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'balance',
                    'total_purchased',
                    'total_used',
                    'available_balance'
                ]
            ]);

        // Purchase credits
        $response = $this->actingAs($this->fundi)
            ->postJson('/api/v1/credits/purchase', [
                'amount' => 10000,
                'payment_method' => 'mobile_money'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'payment',
                'credit_transaction'
            ]);
    }

    /** @test */
    public function admin_can_view_revenue_statistics()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/revenue/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_revenue',
                    'subscription_revenue',
                    'credit_revenue',
                    'boost_revenue',
                    'breakdown'
                ]
            ]);
    }

    /** @test */
    public function job_application_prevents_duplicate_applications()
    {
        // Create active subscription
        FundiSubscription::create([
            'user_id' => $this->fundi->id,
            'subscription_tier_id' => $this->freeTier->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'remaining_applications' => 5,
            'last_reset_at' => now(),
        ]);

        // First application should succeed
        $response = $this->actingAs($this->fundi)
            ->postJson("/api/v1/jobs/{$this->job->id}/apply", [
                'message' => 'First application'
            ]);

        $response->assertStatus(200);

        // Second application should fail
        $response = $this->actingAs($this->fundi)
            ->postJson("/api/v1/jobs/{$this->job->id}/apply", [
                'message' => 'Second application'
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You have already applied to this job'
            ]);
    }

    /** @test */
    public function customer_contact_protection_works()
    {
        // Get job details without payment
        $response = $this->actingAs($this->fundi)
            ->getJson("/api/v1/jobs/{$this->job->id}");

        $response->assertStatus(200);
        
        $jobData = $response->json();
        
        // Customer contact should be protected
        if (isset($jobData['customer'])) {
            $this->assertArrayHasKey('contact_locked', $jobData['customer']);
            $this->assertTrue($jobData['customer']['contact_locked']);
        }
    }
}
