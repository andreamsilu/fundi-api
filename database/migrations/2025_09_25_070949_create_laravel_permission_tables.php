<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * NOTE: This migration is superseded by 2025_09_25_074243_recreate_tables_for_laravel_permission.php
     * Keeping for migration history but skipping execution
     */
    public function up(): void
    {
        // Skip - tables will be created by later migration (074243)
        // This migration is kept for history but is no longer needed
        return;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
    }
};
