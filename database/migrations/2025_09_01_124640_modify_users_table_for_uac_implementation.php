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
        Schema::table('users', function (Blueprint $table) {
            // Remove the role enum field since roles will be managed through Spatie's model_has_roles table
            $table->dropColumn('role');
            
            // Add any missing fields that might be needed for UAC
            $table->string('current_role')->nullable()->after('user_type')->comment('Current active role for multi-role users');
            $table->timestamp('last_role_switch')->nullable()->after('current_role')->comment('When user last switched roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
                    // Restore the role field
        $table->enum('role', [
            'customer', 'fundi', 'businessCustomer', 'businessProvider', 
            'admin', 'moderator', 'support'
        ])->default('customer')->after('user_type');
            
            // Remove the UAC-specific fields
            $table->dropColumn(['current_role', 'last_role_switch']);
        });
    }
};
