<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add primary category/profession to fundi profiles
     */
    public function up(): void
    {
        Schema::table('fundi_profiles', function (Blueprint $table) {
            // Add category_id to link fundi to their primary profession
            $table->foreignId('category_id')->nullable()->after('user_id')->constrained('categories')->onDelete('set null');
            
            // Add index for filtering by category
            $table->index('category_id', 'idx_fundi_profiles_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fundi_profiles', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex('idx_fundi_profiles_category');
            $table->dropColumn('category_id');
        });
    }
};
