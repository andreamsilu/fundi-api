<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('payments_enabled')->default(true);
            $table->enum('payment_model', ['subscription', 'pay_per_application', 'pay_per_job', 'hybrid', 'free'])->default('free');
            
            // Individual payment controls
            $table->boolean('subscription_enabled')->default(false);
            $table->decimal('subscription_fee', 10, 2)->nullable();
            $table->string('subscription_period', 20)->default('monthly'); // monthly, yearly
            
            $table->boolean('job_application_fee_enabled')->default(false);
            $table->decimal('job_application_fee', 10, 2)->nullable();
            
            $table->boolean('job_posting_fee_enabled')->default(false);
            $table->decimal('job_posting_fee', 10, 2)->nullable();
            
            // Legacy fields for backward compatibility
            $table->decimal('application_fee', 10, 2)->nullable();
            $table->decimal('job_post_fee', 10, 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};
