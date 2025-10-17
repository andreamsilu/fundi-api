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
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Add transaction_id column for storing external order IDs from payment gateways
            $table->string('transaction_id')->unique()->nullable()->after('id');
            // Add gateway_reference for storing payment gateway references
            $table->string('gateway_reference')->nullable()->after('payment_reference');
            // Add completed_at timestamp
            $table->timestamp('completed_at')->nullable()->after('paid_at');
            
            // Add index for transaction_id
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Drop the added columns
            $table->dropColumn(['transaction_id', 'gateway_reference', 'completed_at']);
        });
    }
};
