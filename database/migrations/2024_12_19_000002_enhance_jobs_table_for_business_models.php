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
        Schema::table('service_jobs', function (Blueprint $table) {
            // Business model and job type fields
            $table->enum('business_model', [
                'c2c',    // Consumer to Consumer
                'b2c',    // Business to Consumer
                'c2b',    // Consumer to Business
                'b2b'     // Business to Business
            ])->default('c2c')->after('status');
            
            $table->enum('job_type', [
                // Individual jobs (C2C, B2C)
                'homeRepair',         // Home repair and maintenance
                'personalService',    // Personal services (cleaning, etc.)
                'eventService',       // Event-related services
                'consultation',       // Professional consultation
                
                // Business jobs (B2B, C2B)
                'commercialRepair',   // Commercial property repair
                'construction',       // Construction projects
                'maintenance',        // Ongoing maintenance contracts
                'installation',       // Equipment installation
                'consulting',         // Business consulting
                'training',           // Staff training
                'audit',              // Business audits
                'compliance',         // Regulatory compliance
                'digitalService',     // Digital/IT services
                'marketing',          // Marketing services
                'legal',              // Legal services
                'accounting',         // Accounting services
                'hr',                 // Human resources
                'logistics',          // Logistics and supply chain
                'security',           // Security services
                'cleaning',           // Commercial cleaning
                'catering',           // Corporate catering
                'transportation',     // Transportation services
                'equipment',          // Equipment rental/repair
                'emergency'           // Emergency services
            ])->default('homeRepair')->after('business_model');
            
            // Enhanced job details
            $table->text('detailed_description')->nullable()->after('description');
            $table->json('requirements')->nullable()->after('detailed_description');
            $table->json('skills_required')->nullable()->after('requirements');
            $table->json('certifications_required')->nullable()->after('skills_required');
            $table->integer('experience_required')->nullable()->after('certifications_required');
            $table->json('tools_required')->nullable()->after('experience_required');
            $table->boolean('insurance_required')->default(false)->after('tools_required');
            $table->boolean('license_required')->default(false)->after('insurance_required');
            
            // Job timeline
            $table->date('start_date')->nullable()->after('license_required');
            $table->date('end_date')->nullable()->after('start_date');
            $table->json('milestones')->nullable()->after('end_date');
            $table->boolean('onsite_required')->default(true)->after('milestones');
            $table->string('onsite_location')->nullable()->after('onsite_required');
            
            // Payment details
            $table->enum('payment_type', [
                'fixed',      // Fixed amount
                'hourly',     // Hourly rate
                'daily',      // Daily rate
                'milestone',  // Milestone-based
                'negotiable'  // Negotiable
            ])->default('fixed')->after('onsite_location');
            
            $table->decimal('budget_min', 15, 2)->nullable()->after('payment_type');
            $table->decimal('budget_max', 15, 2)->nullable()->after('budget_min');
            $table->decimal('fixed_amount', 15, 2)->nullable()->after('budget_max');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('fixed_amount');
            $table->decimal('daily_rate', 10, 2)->nullable()->after('hourly_rate');
            
            $table->json('accepted_payment_methods')->nullable()->after('daily_rate');
            $table->enum('payment_schedule', [
                'immediate',  // Pay immediately
                'net7',       // Net 7 days
                'net15',      // Net 15 days
                'net30',      // Net 30 days
                'net60',      // Net 60 days
                'milestone',  // Milestone-based
                'completion'  // Upon completion
            ])->default('completion')->after('accepted_payment_methods');
            
            // Business requirements
            $table->boolean('requires_contract')->default(false)->after('payment_schedule');
            $table->boolean('requires_invoice')->default(false)->after('requires_contract');
            $table->boolean('requires_insurance')->default(false)->after('requires_invoice');
            $table->boolean('requires_license')->default(false)->after('requires_insurance');
            $table->boolean('requires_background_check')->default(false)->after('requires_license');
            
            // Job metadata
            $table->json('tags')->nullable()->after('requires_background_check');
            $table->enum('urgency', ['low', 'medium', 'high', 'urgent'])->default('medium')->after('tags');
            $table->boolean('is_featured')->default(false)->after('urgency');
            $table->integer('view_count')->default(0)->after('is_featured');
            $table->integer('proposal_count')->default(0)->after('view_count');
            $table->timestamp('deadline')->nullable()->after('proposal_count');
            
            // Enhanced location
            $table->decimal('latitude', 10, 8)->nullable()->after('location');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('city')->nullable()->after('longitude');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->string('postal_code')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'business_model', 'job_type', 'detailed_description', 'requirements',
                'skills_required', 'certifications_required', 'experience_required',
                'tools_required', 'insurance_required', 'license_required', 'start_date',
                'end_date', 'milestones', 'onsite_required', 'onsite_location',
                'payment_type', 'budget_min', 'budget_max', 'fixed_amount',
                'hourly_rate', 'daily_rate', 'accepted_payment_methods', 'payment_schedule',
                'requires_contract', 'requires_invoice', 'requires_insurance',
                'requires_license', 'requires_background_check', 'tags', 'urgency',
                'is_featured', 'view_count', 'proposal_count', 'deadline',
                'latitude', 'longitude', 'city', 'state', 'country', 'postal_code'
            ]);
        });
    }
}; 