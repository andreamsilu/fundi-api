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
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Subscription, Pay Per Use
            $table->string('type'); // free, subscription, pay_per_use
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0); // Price for subscription or per-use
            $table->string('billing_cycle')->nullable(); // monthly, yearly for subscription
            $table->json('features')->nullable(); // Available features for this plan
            $table->json('limits')->nullable(); // Usage limits for this plan
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};
