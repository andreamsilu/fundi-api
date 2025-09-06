<?php

namespace Database\Seeders;

use App\Models\SubscriptionTier;
use Illuminate\Database\Seeder;

class SubscriptionTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'monthly_price_tzs' => 0,
                'included_job_applications' => 5,
                'features' => [
                    'basic_job_applications' => 5,
                    'profile_visibility' => 'standard',
                    'customer_contact' => 'after_application',
                    'basic_support' => true,
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'monthly_price_tzs' => 15000,
                'included_job_applications' => 25,
                'features' => [
                    'basic_job_applications' => 25,
                    'profile_visibility' => 'enhanced',
                    'customer_contact' => 'after_application',
                    'priority_support' => true,
                    'verified_badge' => false,
                    'profile_boost' => false,
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'monthly_price_tzs' => 35000,
                'included_job_applications' => 75,
                'features' => [
                    'basic_job_applications' => 75,
                    'profile_visibility' => 'premium',
                    'customer_contact' => 'after_application',
                    'priority_support' => true,
                    'verified_badge' => true,
                    'profile_boost' => true,
                    'advanced_analytics' => true,
                    'priority_listing' => true,
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($tiers as $tier) {
            SubscriptionTier::updateOrCreate(
                ['slug' => $tier['slug']],
                $tier
            );
        }
    }
}
