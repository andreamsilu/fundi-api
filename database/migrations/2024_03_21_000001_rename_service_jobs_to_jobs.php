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
        // First, rename the table
        Schema::rename('service_jobs', 'jobs');

        // Then modify the table structure
        Schema::table('jobs', function (Blueprint $table) {
            // Drop any columns that don't exist in the new schema
            if (Schema::hasColumn('jobs', 'service_category_id')) {
                $table->dropForeign(['service_category_id']);
                $table->renameColumn('service_category_id', 'category_id');
            }

            // Add any missing columns
            if (!Schema::hasColumn('jobs', 'title')) {
                $table->string('title')->after('user_id');
            }

            // Modify status column if needed
            if (Schema::hasColumn('jobs', 'status')) {
                $table->enum('status', ['open', 'booked', 'closed'])->default('open')->change();
            } else {
                $table->enum('status', ['open', 'booked', 'closed'])->default('open');
            }

            // Update foreign key
            $table->foreign('category_id')
                ->references('id')
                ->on('service_categories')
                ->onDelete('restrict');

            // Add indexes for better performance
            $table->index('status');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            // Remove indexes
            $table->dropIndex(['status']);
            $table->dropIndex(['category_id']);

            // Revert status column if it was added
            if (Schema::hasColumn('jobs', 'status')) {
                $table->string('status')->change();
            }

            // Drop title column if it was added
            if (Schema::hasColumn('jobs', 'title')) {
                $table->dropColumn('title');
            }

            // Revert category_id to service_category_id
            if (Schema::hasColumn('jobs', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->renameColumn('category_id', 'service_category_id');
                $table->foreign('service_category_id')
                    ->references('id')
                    ->on('service_categories')
                    ->onDelete('restrict');
            }
        });

        // Rename the table back
        Schema::rename('jobs', 'service_jobs');
    }
}; 