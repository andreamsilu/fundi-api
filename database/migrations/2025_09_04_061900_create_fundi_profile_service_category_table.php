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
        Schema::create('fundi_profile_service_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fundi_profile_id')->constrained('fundi_profiles')->onDelete('cascade');
            $table->foreignId('service_category_id')->constrained('service_categories')->onDelete('cascade');
            $table->timestamps();

            // Ensure unique combinations
            $table->unique(['fundi_profile_id', 'service_category_id'], 'fundi_profile_category_unique');
            
            // Add indexes for better performance
            $table->index('fundi_profile_id');
            $table->index('service_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundi_profile_service_category');
    }
};
