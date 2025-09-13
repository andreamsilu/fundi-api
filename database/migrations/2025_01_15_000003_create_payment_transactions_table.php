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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_plan_id')->constrained()->onDelete('cascade');
            $table->string('transaction_type'); // subscription, pay_per_use, job_posting, fundi_application
            $table->string('reference_id')->nullable(); // Job ID, Application ID, etc.
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('TZS');
            $table->string('payment_method')->nullable(); // mpesa, bank_transfer, card
            $table->string('payment_reference')->nullable(); // External payment reference
            $table->string('status'); // pending, completed, failed, refunded
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional transaction data
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['transaction_type']);
            $table->index(['payment_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
