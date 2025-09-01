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
            // Enhanced role enum with new business roles
            $table->enum('role', [
                'client',           // C2C, B2C: Individual seeking services
                'fundi',            // C2C, C2B: Individual providing services
                'businessClient',   // B2B, B2C: Business seeking services
                'businessProvider', // B2B, C2B: Business providing services
                'admin',            // Platform administrator
                'moderator',        // Platform moderator
                'support'           // Customer support
            ])->default('client')->change();
            
            // New user type field
            $table->enum('user_type', [
                'individual',   // C2C, C2B, B2C
                'business',     // B2B, B2C, C2B
                'enterprise',   // Large business (B2B)
                'government',   // Government entity (B2B)
                'nonprofit'     // Non-profit organization (B2B)
            ])->default('individual')->after('role');
            
            // Business profile fields (nullable for individual users)
            $table->string('business_name')->nullable()->after('user_type');
            $table->string('business_type')->nullable()->after('business_name');
            $table->string('registration_number')->nullable()->after('business_type');
            $table->string('tax_id')->nullable()->after('registration_number');
            $table->string('website')->nullable()->after('tax_id');
            $table->text('business_description')->nullable()->after('website');
            $table->json('services_offered')->nullable()->after('business_description');
            $table->json('industries')->nullable()->after('services_offered');
            $table->integer('employee_count')->nullable()->after('industries');
            $table->integer('year_established')->nullable()->after('employee_count');
            $table->string('license_number')->nullable()->after('year_established');
            $table->json('certifications')->nullable()->after('license_number');
            $table->json('contact_persons')->nullable()->after('certifications');
            $table->json('business_hours')->nullable()->after('contact_persons');
            $table->json('payment_methods')->nullable()->after('business_hours');
            $table->decimal('average_project_value', 15, 2)->nullable()->after('payment_methods');
            $table->integer('completed_projects')->default(0)->after('average_project_value');
            
            // Individual profile fields (nullable for business users)
            $table->text('bio')->nullable()->after('completed_projects');
            $table->json('skills')->nullable()->after('bio');
            $table->json('specializations')->nullable()->after('skills');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('specializations');
            $table->decimal('daily_rate', 10, 2)->nullable()->after('hourly_rate');
            $table->decimal('project_rate', 10, 2)->nullable()->after('daily_rate');
            $table->json('individual_certifications')->nullable()->after('project_rate');
            $table->integer('years_experience')->nullable()->after('individual_certifications');
            $table->json('languages')->nullable()->after('years_experience');
            $table->json('availability')->nullable()->after('languages');
            $table->json('preferred_job_types')->nullable()->after('availability');
            $table->json('portfolio')->nullable()->after('preferred_job_types');
            
            // Enhanced location and contact fields
            $table->string('email')->nullable()->after('phone');
            $table->string('address')->nullable()->after('email');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->string('postal_code')->nullable()->after('country');
            $table->decimal('latitude', 10, 8)->nullable()->after('postal_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // Verification and status fields
            $table->boolean('is_verified')->default(false)->after('longitude');
            $table->boolean('is_available')->default(true)->after('is_verified');
            $table->timestamp('email_verified_at')->nullable()->after('is_available');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->timestamp('profile_completed_at')->nullable()->after('phone_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert role enum to original
            $table->enum('role', ['customer', 'fundi', 'admin'])->default('customer')->change();
            
            // Drop all new columns
            $table->dropColumn([
                'user_type', 'business_name', 'business_type', 'registration_number',
                'tax_id', 'website', 'business_description', 'services_offered',
                'industries', 'employee_count', 'year_established', 'license_number',
                'certifications', 'contact_persons', 'business_hours', 'payment_methods',
                'average_project_value', 'completed_projects', 'bio', 'skills',
                'specializations', 'hourly_rate', 'daily_rate', 'project_rate',
                'individual_certifications', 'years_experience', 'languages',
                'availability', 'preferred_job_types', 'portfolio', 'email',
                'address', 'city', 'state', 'country', 'postal_code', 'latitude',
                'longitude', 'is_verified', 'is_available', 'email_verified_at',
                'phone_verified_at', 'profile_completed_at'
            ]);
        });
    }
}; 