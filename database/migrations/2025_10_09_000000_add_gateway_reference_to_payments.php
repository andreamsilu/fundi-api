<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds gateway_reference field to payments table to support ZenoPay
     * and other modern payment gateways (replacing legacy pesapal_reference)
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add new gateway_reference column (nullable for backward compatibility)
            $table->string('gateway_reference', 100)->nullable()->after('pesapal_reference');
            $table->index('gateway_reference');
        });

        // Copy existing pesapal_reference values to gateway_reference
        DB::table('payments')->update([
            'gateway_reference' => DB::raw('pesapal_reference')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['gateway_reference']);
            $table->dropColumn('gateway_reference');
        });
    }
};

