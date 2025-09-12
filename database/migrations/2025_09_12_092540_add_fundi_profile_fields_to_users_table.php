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
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('nida_number');
            $table->string('email')->nullable()->after('full_name');
            $table->string('location')->nullable()->after('email');
            $table->text('bio')->nullable()->after('location');
            $table->json('skills')->nullable()->after('bio');
            $table->json('languages')->nullable()->after('skills');
            $table->string('veta_certificate')->nullable()->after('languages');
            $table->json('portfolio_images')->nullable()->after('veta_certificate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'full_name',
                'email',
                'location',
                'bio',
                'skills',
                'languages',
                'veta_certificate',
                'portfolio_images',
            ]);
        });
    }
};