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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('status')->default('pending');
            $table->string('payment_method');
            $table->string('payment_provider');
            $table->string('payment_provider_id')->nullable();
            $table->string('payment_provider_status')->nullable();
            $table->json('payment_provider_response')->nullable();
            $table->json('metadata')->nullable();
            $table->morphs('payable');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['payment_provider', 'payment_provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
}; 