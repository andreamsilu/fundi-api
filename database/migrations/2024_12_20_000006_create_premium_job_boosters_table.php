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
        Schema::create('premium_job_boosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('service_jobs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Customer who paid for boost
            $table->enum('boost_type', ['featured', 'urgent', 'premium']); // Type of boost
            $table->decimal('boost_fee', 10, 2); // Fee paid for the boost
            $table->enum('business_model', ['c2c', 'b2c', 'c2b', 'b2b']); // Business model for pricing
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['job_id', 'status']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('premium_job_boosters');
    }
};
