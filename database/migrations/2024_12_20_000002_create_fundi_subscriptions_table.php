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
        Schema::create('fundi_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_tier_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'expired', 'cancelled', 'suspended'])->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->integer('remaining_applications'); // Remaining applications for current period
            $table->timestamp('last_reset_at'); // When applications were last reset
            $table->json('metadata')->nullable(); // Additional subscription data
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundi_subscriptions');
    }
};
