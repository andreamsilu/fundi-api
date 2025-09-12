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
        Schema::create('work_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fundi_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_posting_id')->constrained('job_postings')->onDelete('cascade');
            $table->foreignId('portfolio_id')->nullable()->constrained('portfolio')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->json('work_images')->nullable(); // Array of image URLs
            $table->json('work_files')->nullable(); // Array of file URLs
            $table->enum('status', ['submitted', 'approved', 'rejected', 'revision_requested'])->default('submitted');
            $table->text('rejection_reason')->nullable();
            $table->text('revision_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['fundi_id', 'status']);
            $table->index(['job_posting_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_submissions');
    }
};