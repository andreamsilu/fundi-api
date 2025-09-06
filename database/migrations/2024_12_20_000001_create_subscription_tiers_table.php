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
        Schema::create('subscription_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Standard, Premium
            $table->string('slug')->unique(); // free, standard, premium
            $table->decimal('monthly_price_tzs', 10, 2); // Price in TZS
            $table->integer('included_job_applications'); // Number of free applications per month
            $table->json('features'); // Additional features like visibility boost, verified badge
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_tiers');
    }
};
