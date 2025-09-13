<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentPlan;

class PaymentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Free Plan (Default)
        PaymentPlan::create([
            'name' => 'Free Plan',
            'type' => 'free',
            'description' => 'Unlimited access to all platform features',
            'price' => 0.00,
            'features' => [
                'unlimited_job_posting',
                'unlimited_job_applications',
                'unlimited_fundi_browsing',
                'unlimited_messaging',
                'basic_support',
                'profile_management',
                'notifications',
            ],
            'limits' => [
                'monthly_jobs' => null, // Unlimited
                'monthly_applications' => null, // Unlimited
                'monthly_messages' => null, // Unlimited
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        // Basic Subscription Plan
        PaymentPlan::create([
            'name' => 'Basic Subscription',
            'type' => 'subscription',
            'description' => 'Monthly subscription with enhanced features',
            'price' => 5000.00, // 5000 TZS
            'billing_cycle' => 'monthly',
            'features' => [
                'unlimited_job_posting',
                'unlimited_job_applications',
                'unlimited_fundi_browsing',
                'unlimited_messaging',
                'priority_support',
                'advanced_search_filters',
                'profile_analytics',
                'priority_job_listing',
            ],
            'limits' => [
                'monthly_jobs' => null, // Unlimited
                'monthly_applications' => null, // Unlimited
                'monthly_messages' => null, // Unlimited
            ],
            'is_active' => true,
            'is_default' => false,
        ]);

        // Premium Subscription Plan
        PaymentPlan::create([
            'name' => 'Premium Subscription',
            'type' => 'subscription',
            'description' => 'Yearly subscription with premium features',
            'price' => 50000.00, // 50000 TZS
            'billing_cycle' => 'yearly',
            'features' => [
                'unlimited_job_posting',
                'unlimited_job_applications',
                'unlimited_fundi_browsing',
                'unlimited_messaging',
                'premium_support',
                'advanced_search_filters',
                'detailed_analytics',
                'priority_job_listing',
                'custom_branding',
                'api_access',
            ],
            'limits' => [
                'monthly_jobs' => null, // Unlimited
                'monthly_applications' => null, // Unlimited
                'monthly_messages' => null, // Unlimited
            ],
            'is_active' => true,
            'is_default' => false,
        ]);

        // Pay Per Job Plan
        PaymentPlan::create([
            'name' => 'Pay Per Job',
            'type' => 'pay_per_use',
            'description' => 'Pay only when posting a job',
            'price' => 500.00, // 500 TZS per job
            'features' => [
                'job_posting',
                'fundi_browsing',
                'messaging',
                'basic_support',
            ],
            'limits' => [
                'job_posting_cost' => 500.00,
                'application_cost' => 100.00,
            ],
            'is_active' => true,
            'is_default' => false,
        ]);

        // Pay Per Application Plan
        PaymentPlan::create([
            'name' => 'Pay Per Application',
            'type' => 'pay_per_use',
            'description' => 'Pay only when applying to jobs',
            'price' => 200.00, // 200 TZS per application
            'features' => [
                'job_applications',
                'fundi_browsing',
                'messaging',
                'basic_support',
            ],
            'limits' => [
                'job_posting_cost' => 0.00, // Free
                'application_cost' => 200.00,
            ],
            'is_active' => true,
            'is_default' => false,
        ]);
    }
}
