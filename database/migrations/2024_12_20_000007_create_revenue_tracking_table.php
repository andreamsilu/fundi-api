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
        Schema::create('revenue_tracking', function (Blueprint $table) {
            $table->id();
            $table->enum('revenue_type', ['subscription', 'credits', 'job_boost', 'application_fee']);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Fundi or customer
            $table->foreignId('job_id')->nullable()->constrained('service_jobs')->onDelete('set null');
            $table->enum('business_model', ['c2c', 'b2c', 'c2b', 'b2b'])->nullable();
            $table->decimal('amount', 10, 2); // Revenue amount in TZS
            $table->string('currency', 3)->default('TZS');
            $table->string('description'); // Description of the revenue
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('credit_transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('subscription_id')->nullable()->constrained('fundi_subscriptions')->onDelete('set null');
            $table->foreignId('booster_id')->nullable()->constrained('premium_job_boosters')->onDelete('set null');
            $table->date('revenue_date'); // Date the revenue was generated
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['revenue_type', 'revenue_date']);
            $table->index(['business_model', 'revenue_date']);
            $table->index(['user_id', 'revenue_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_tracking');
    }
};
