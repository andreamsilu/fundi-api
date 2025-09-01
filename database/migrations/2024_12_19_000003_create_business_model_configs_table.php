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
        Schema::create('business_model_configs', function (Blueprint $table) {
            $table->id();
            $table->enum('business_model', [
                'c2c',    // Consumer to Consumer
                'b2c',    // Business to Consumer
                'c2b',    // Consumer to Business
                'b2b'     // Business to Business
            ])->unique();
            
            // Allowed roles for clients and providers
            $table->json('allowed_client_roles')->nullable();
            $table->json('allowed_provider_roles')->nullable();
            
            // Allowed user types
            $table->json('allowed_client_types')->nullable();
            $table->json('allowed_provider_types')->nullable();
            
            // Supported job types
            $table->json('supported_job_types')->nullable();
            
            // Payment configuration
            $table->json('supported_payment_methods')->nullable();
            $table->json('supported_payment_schedules')->nullable();
            $table->decimal('minimum_transaction_amount', 15, 2)->default(0);
            $table->decimal('maximum_transaction_amount', 15, 2)->default(999999.99);
            
            // Requirements
            $table->boolean('requires_contract')->default(false);
            $table->boolean('requires_invoice')->default(false);
            $table->boolean('requires_insurance')->default(false);
            $table->boolean('requires_license')->default(false);
            $table->boolean('requires_background_check')->default(false);
            $table->json('additional_requirements')->nullable();
            
            // Platform fees
            $table->decimal('platform_fee_percentage', 5, 2)->default(5.00);
            $table->decimal('platform_fee_fixed', 10, 2)->default(0.00);
            $table->decimal('minimum_fee', 10, 2)->default(0.00);
            $table->decimal('maximum_fee', 10, 2)->default(999.99);
            
            // Features and capabilities
            $table->json('enabled_features')->nullable();
            $table->json('restricted_features')->nullable();
            
            // Business model description
            $table->text('description')->nullable();
            $table->text('client_description')->nullable();
            $table->text('provider_description')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_model_configs');
    }
}; 