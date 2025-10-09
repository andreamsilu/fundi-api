<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds additional pricing fields to admin_settings table
     * so all platform fees can be managed from admin panel
     */
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            // Additional payment fees
            $table->decimal('premium_profile_fee', 10, 2)->default(500)->after('job_posting_fee');
            $table->decimal('featured_job_fee', 10, 2)->default(2000)->after('premium_profile_fee');
            $table->decimal('subscription_monthly_fee', 10, 2)->default(5000)->after('featured_job_fee');
            $table->decimal('subscription_yearly_fee', 10, 2)->default(50000)->after('subscription_monthly_fee');
            $table->decimal('platform_commission_percentage', 5, 2)->default(10)->after('subscription_yearly_fee');
        });

        // Update existing record with default values if exists
        DB::table('admin_settings')->update([
            'premium_profile_fee' => 500,
            'featured_job_fee' => 2000,
            'subscription_monthly_fee' => 5000,
            'subscription_yearly_fee' => 50000,
            'platform_commission_percentage' => 10,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn([
                'premium_profile_fee',
                'featured_job_fee',
                'subscription_monthly_fee',
                'subscription_yearly_fee',
                'platform_commission_percentage',
            ]);
        });
    }
};

