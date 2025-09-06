<?php

namespace Tests\Feature;

use App\Models\FundiCredits;
use App\Models\FundiSubscription;
use App\Models\Job;
use App\Models\JobApplicationFee;
use App\Models\PremiumJobBooster;
use App\Models\RevenueTracking;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Services\MonetizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MonetizationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected MonetizationService $monetizationService;
    protected User $fundi;
    protected User $customer;
    protected SubscriptionTier $freeTier;
    protected SubscriptionTier $premiumTier;
    protected Job $job;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->monetizationService = app(MonetizationService::class);
        
        // Create test users
        $this->fundi = User::factory()->create(['role' => 'fundi']);
        $this->customer = User::factory()->create(['role' => 'customer']);
        
        // Create subscription tiers
        $this->freeTier = SubscriptionTier::create([
            'name' => 'Free',
            'slug' => 'free',
            'monthly_price_tzs' => 0,
            'included_job_applications' => 5,
            'features' => ['basic_support' => true],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        $this->premiumTier = SubscriptionTier::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'monthly_price_tzs' => 35000,
            'included_job_applications' => 75,
            'features' => ['verified_badge' => true, 'priority_support' => true],
            'is_active' => true,
            'sort_order' => 3,
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
    public function fundi_can_subscribe_to_premium_tier()
    {
        $response = $this->actingAs($this->fundi)
            ->postJson('/api/v1/subscriptions/subscribe', [
                'tier_id' => $this->premiumTier->id
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'payment',
                'subscription'
            ]);

        $this->assertDatabaseHas('fundi_subscriptions', [
            'user_id' => $this->fundi->id,
            'subscription_tier_id' => $this->premiumTier->id,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function fundi_can_purchase_credits()
    {
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

        $this->assertDatabaseHas('fundi_credits', [
            'user_id' => $this->fundi->id,
            'balance' => 10000
        ]);
    }

    /** @test */
    public function fundi_can_apply_to_job_with_subscription()
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

        $this->assertDatabaseHas('job_application_fees', [
            'job_id' => $this->job->id,
            'fundi_id' => $this->fundi->id,
            'payment_type' => 'subscription',
            'status' => 'paid'
        ]);
    }

    /** @test */
    public function fundi_can_apply_to_job_with_credits()
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

        $this->assertDatabaseHas('job_application_fees', [
            'job_id' => $this->job->id,
            'fundi_id' => $this->fundi->id,
            'payment_type' => 'credits',
            'status' => 'paid'
        ]);
    }

    /** @test */
    public function fundi_cannot_apply_without_payment()
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

        $this->assertDatabaseHas('premium_job_boosters', [
            'job_id' => $this->job->id,
            'user_id' => $this->customer->id,
            'boost_type' => 'featured',
            'status' => 'active'
        ]);

        $this->assertTrue($this->job->fresh()->is_featured);
    }

    /** @test */
    public function application_fee_calculation_is_correct()
    {
        // Test different job values
        $lowValueJob = Job::factory()->create(['budget_max' => 15000]);
        $mediumValueJob = Job::factory()->create(['budget_max' => 75000]);
        $highValueJob = Job::factory()->create(['budget_max' => 200000]);

        $lowFee = $this->monetizationService->calculateApplicationFee($lowValueJob);
        $mediumFee = $this->monetizationService->calculateApplicationFee($mediumValueJob);
        $highFee = $this->monetizationService->calculateApplicationFee($highValueJob);

        $this->assertEquals(500, $lowFee); // < 20,000 TZS
        $this->assertEquals(1000, $mediumFee); // 20,000–100,000 TZS
        $this->assertGreaterThan(2000, $highFee); // > 100,000 TZS
        $this->assertLessThanOrEqual(5000, $highFee); // Max 5,000 TZS
    }

    /** @test */
    public function boost_fee_calculation_is_correct()
    {
        $c2cFee = $this->monetizationService->calculateBoostFee('c2c');
        $b2cFee = $this->monetizationService->calculateBoostFee('b2c');
        $b2bFee = $this->monetizationService->calculateBoostFee('b2b');
        $c2bFee = $this->monetizationService->calculateBoostFee('c2b');

        $this->assertEquals(500, $c2cFee);
        $this->assertEquals(1000, $b2cFee);
        $this->assertEquals(10000, $b2bFee);
        $this->assertEquals(5000, $c2bFee);
    }

    /** @test */
    public function revenue_tracking_works_correctly()
    {
        // Create a subscription
        $subscription = FundiSubscription::create([
            'user_id' => $this->fundi->id,
            'subscription_tier_id' => $this->premiumTier->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'remaining_applications' => 75,
            'last_reset_at' => now(),
        ]);

        // Track revenue
        $this->monetizationService->trackRevenue(
            'subscription',
            $this->fundi,
            null,
            35000,
            'Monthly subscription payment'
        );

        $this->assertDatabaseHas('revenue_tracking', [
            'revenue_type' => 'subscription',
            'user_id' => $this->fundi->id,
            'amount' => 35000,
            'currency' => 'TZS'
        ]);
    }

    /** @test */
    public function admin_can_view_revenue_statistics()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create some revenue data
        RevenueTracking::create([
            'revenue_type' => 'subscription',
            'user_id' => $this->fundi->id,
            'amount' => 35000,
            'currency' => 'TZS',
            'description' => 'Test revenue',
            'revenue_date' => now()->toDateString(),
        ]);

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
    public function subscription_reset_works_correctly()
    {
        $subscription = FundiSubscription::create([
            'user_id' => $this->fundi->id,
            'subscription_tier_id' => $this->freeTier->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addMonth(),
            'remaining_applications' => 0,
            'last_reset_at' => now()->subMonth(),
        ]);

        $subscription->resetApplications();

        $this->assertEquals(5, $subscription->fresh()->remaining_applications);
        $this->assertTrue($subscription->fresh()->last_reset_at->isToday());
    }

    /** @test */
    public function credit_transactions_are_tracked_correctly()
    {
        $credits = FundiCredits::create([
            'user_id' => $this->fundi->id,
            'balance' => 10000,
            'total_purchased' => 10000,
            'total_used' => 0,
        ]);

        // Use credits
        $transaction = $credits->useCredits(1000, 'Test usage', $this->job->id);

        $this->assertNotNull($transaction);
        $this->assertEquals(9000, $credits->fresh()->balance);
        $this->assertEquals(1000, $credits->fresh()->total_used);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->fundi->id,
            'type' => 'usage',
            'amount' => 1000,
            'job_id' => $this->job->id
        ]);
    }
}
