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
            // Add customer_id column to track who created the booking
            $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // Add service_job_id column to reference the service job
            $table->foreignId('service_job_id')->nullable()->constrained('service_jobs')->onDelete('cascade');
            
            // Add description column for booking details
            $table->text('description')->nullable();
            
            // Add scheduled_date and scheduled_time columns
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            
            // Add location column
            $table->string('location')->nullable();
            
            // Add notes column
            $table->text('notes')->nullable();
            
            // Add estimated_duration and actual_duration columns
            $table->integer('estimated_duration')->nullable(); // in minutes
            $table->integer('actual_duration')->nullable(); // in minutes
            
            // Add estimated_cost and actual_cost columns
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            
            // Add payment_status and payment_method columns
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            
            // Update status enum to include more options
            $table->dropColumn('status');
        });
        
        // Add the new status column with updated enum values
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'confirmed', 'in_progress', 'completed', 'cancelled', 'rejected'])->default('pending');
        });
        
        // Add indexes for better performance
        Schema::table('bookings', function (Blueprint $table) {
            try {
                $table->index(['customer_id', 'status']);
            } catch (\Throwable $e) {
                // ignore if index already exists
            }
            
            try {
                $table->index(['fundi_id', 'status']);
            } catch (\Throwable $e) {
                // ignore if index already exists
            }
            
            try {
                $table->index(['service_job_id', 'status']);
            } catch (\Throwable $e) {
                // ignore if index already exists
            }
            
            try {
                $table->index('scheduled_date');
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
            // Drop indexes
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['fundi_id', 'status']);
            $table->dropIndex(['service_job_id', 'status']);
            $table->dropIndex(['scheduled_date']);
            
            // Drop foreign key constraints
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['service_job_id']);
            
            // Drop columns
            $table->dropColumn([
                'customer_id',
                'service_job_id',
                'description',
                'scheduled_date',
                'scheduled_time',
                'location',
                'notes',
                'estimated_duration',
                'actual_duration',
                'estimated_cost',
                'actual_cost',
                'payment_status',
                'payment_method',
                'status'
            ]);
            
            // Restore original status column
            $table->enum('status', ['pending', 'accepted', 'declined', 'completed', 'cancelled'])->default('pending');
        });
    }
};
