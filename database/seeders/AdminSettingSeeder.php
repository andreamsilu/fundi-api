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
            'payment_model' => 'subscription',
            'subscription_fee' => 5000.00, // 5000 TZS
            'application_fee' => 1000.00,  // 1000 TZS per application
            'job_post_fee' => 2000.00,     // 2000 TZS per job post
        ]);
    }
}
