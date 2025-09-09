<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\AdminSetting::create([
            'payments_enabled' => true,
            'payment_model' => 'free', // Start with free mode
            
            // Individual payment controls
            'subscription_enabled' => false,
            'subscription_fee' => 5000.00, // 5000 TZS
            'subscription_period' => 'monthly',
            
            'job_application_fee_enabled' => false,
            'job_application_fee' => 1000.00, // 1000 TZS per application
            
            'job_posting_fee_enabled' => false,
            'job_posting_fee' => 2000.00, // 2000 TZS per job post
            
            // Legacy fields for backward compatibility
            'application_fee' => 1000.00,
            'job_post_fee' => 2000.00,
        ]);
    }
}
