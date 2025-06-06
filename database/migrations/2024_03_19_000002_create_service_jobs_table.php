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
        Schema::create('service_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->foreignId('category_id')->constrained('service_categories');
            $table->enum('status', ['open', 'booked', 'completed', 'cancelled'])->default('open');
            $table->decimal('budget', 10, 2)->nullable();
            $table->timestamp('preferred_date')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();

            // Add indexes for common queries
            $table->index(['status', 'category_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_jobs');
    }
}; 