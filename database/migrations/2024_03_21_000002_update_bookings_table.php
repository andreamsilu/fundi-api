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
        Schema::table('bookings', function (Blueprint $table) {
            // Drop columns that are not in the documentation
            $table->dropColumn([
                'proposed_price',
                'proposal',
                'accepted_at',
                'completed_at',
            ]);

            // Rename job_id column if it references service_jobs
            if (Schema::hasColumn('bookings', 'service_job_id')) {
                $table->renameColumn('service_job_id', 'job_id');
            }

            // Update foreign key if needed
            $table->dropForeign(['job_id']);
            $table->foreign('job_id')
                ->references('id')
                ->on('jobs')
                ->onDelete('cascade');

            // Add index for better performance
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Remove index
            $table->dropIndex(['status']);

            // Add back the dropped columns
            $table->decimal('proposed_price', 10, 2)->nullable();
            $table->text('proposal')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Revert foreign key if needed
            $table->dropForeign(['job_id']);
            $table->foreign('job_id')
                ->references('id')
                ->on('service_jobs')
                ->onDelete('cascade');
        });
    }
}; 