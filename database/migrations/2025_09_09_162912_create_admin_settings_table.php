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
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('payments_enabled')->default(true);
            $table->enum('payment_model', ['subscription', 'pay_per_application', 'pay_per_job', 'hybrid'])->default('subscription');
            $table->decimal('subscription_fee', 10, 2)->nullable();
            $table->decimal('application_fee', 10, 2)->nullable();
            $table->decimal('job_post_fee', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};
