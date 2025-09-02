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
        // Add description field to roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->string('description')->nullable()->after('guard_name');
        });

        // Add description field to permissions table
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('description')->nullable()->after('guard_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove description field from roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        // Remove description field from permissions table
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
