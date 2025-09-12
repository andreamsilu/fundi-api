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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'create_jobs', 'manage_users'
            $table->string('display_name'); // e.g., 'Create Jobs', 'Manage Users'
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // e.g., 'jobs', 'users', 'system'
            $table->boolean('is_system_permission')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};