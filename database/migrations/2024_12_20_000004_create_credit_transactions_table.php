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
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['purchase', 'usage', 'refund', 'bonus']); // Transaction type
            $table->decimal('amount', 10, 2); // Amount in TZS
            $table->string('description'); // Description of the transaction
            $table->string('reference')->nullable(); // External reference (payment ID, etc.)
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null'); // Link to payment
            $table->foreignId('job_id')->nullable()->constrained('service_jobs')->onDelete('set null'); // Link to job if applicable
            $table->json('metadata')->nullable(); // Additional transaction data
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
