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
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('location')->nullable()->after('deadline');
            $table->enum('urgency', ['low', 'medium', 'high'])->nullable()->after('location');
            $table->string('preferred_time')->nullable()->after('urgency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['location', 'urgency', 'preferred_time']);
        });
    }
};
