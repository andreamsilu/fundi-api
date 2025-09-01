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
            // Ensure job_id references service_jobs (our domain jobs table)
            try {
                $table->dropForeign(['job_id']);
            } catch (\Throwable $e) {
                // ignore if foreign doesn't exist yet
            }
            $table->foreign('job_id')
                ->references('id')
                ->on('service_jobs')
                ->onDelete('cascade');

            // Ensure index on status exists
            try {
                $table->index('status');
            } catch (\Throwable $e) {
                // ignore if index already exists
            }
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