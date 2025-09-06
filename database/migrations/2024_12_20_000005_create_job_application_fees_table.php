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
        Schema::create('job_application_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('service_jobs')->onDelete('cascade');
            $table->foreignId('fundi_id')->constrained('users')->onDelete('cascade');
            $table->decimal('fee_amount', 10, 2); // Fee charged for this application
            $table->enum('payment_type', ['subscription', 'credits']); // How the fee was paid
            $table->foreignId('credit_transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('subscription_id')->nullable()->constrained('fundi_subscriptions')->onDelete('set null');
            $table->enum('status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->timestamps();

            $table->index(['job_id', 'fundi_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_application_fees');
    }
};
