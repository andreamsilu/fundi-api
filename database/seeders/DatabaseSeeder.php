<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Database Seeder
 * 
 * Comprehensive seeder that populates all database tables with realistic test data
 * Order is critical - respects foreign key dependencies and relationships
 * 
 * Migration Coverage:
 * - users (roles, permissions via Spatie)
 * - categories
 * - admin_settings
 * - payment_plans
 * - fundi_profiles
 * - fundi_applications
 * - fundi_application_sections
 * - job_postings (jobs)
 * - job_media
 * - job_applications
 * - portfolio
 * - portfolio_media
 * - payments
 * - payment_transactions
 * - user_subscriptions
 * - work_submissions
 * - notifications
 * - ratings_reviews
 * - audit_logs
 * - user_sessions
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Seeds are called in dependency order to satisfy foreign key constraints
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting comprehensive database seeding...');
        $this->command->newLine();

        // ========================================
        // PHASE 1: Foundation (No Dependencies)
        // ========================================
        $this->command->info('📋 Phase 1: Foundation Data');
        $this->call([
            RolePermissionSeeder::class,     // Roles & Permissions (Spatie)
            CategorySeeder::class,            // Job Categories
            AdminSettingSeeder::class,        // System Settings
            PaymentPlanSeeder::class,         // Payment Plan Definitions
        ]);
        $this->command->newLine();

        // ========================================
        // PHASE 2: Users & Profiles
        // ========================================
        $this->command->info('👥 Phase 2: Users & Profiles');
        $this->call([
            UserSeeder::class,                // Users with Roles
            UserSessionSeeder::class,         // Active User Sessions
            FundiProfileSeeder::class,        // Fundi Professional Profiles
            FundiApplicationSeeder::class,    // Fundi Applications (customers becoming fundis)
            FundiApplicationSectionSeeder::class, // Multi-step Application Data
        ]);
        $this->command->newLine();

        // ========================================
        // PHASE 3: Jobs & Applications
        // ========================================
        $this->command->info('💼 Phase 3: Jobs & Applications');
        $this->call([
            JobsSeeder::class,                // Job Postings
            JobMediaSeeder::class,            // Job Photos/Documents
            JobApplicationsSeeder::class,     // Fundi Applications to Jobs
        ]);
        $this->command->newLine();

        // ========================================
        // PHASE 4: Portfolio & Work
        // ========================================
        $this->command->info('🎨 Phase 4: Portfolio & Work Submissions');
        $this->call([
            PortfoliosSeeder::class,          // Fundi Portfolio Items
            PortfolioMediaSeeder::class,      // Portfolio Images/Videos
            WorkSubmissionSeeder::class,      // Completed Work Submissions
        ]);
        $this->command->newLine();

        // ========================================
        // PHASE 5: Payments & Transactions
        // ========================================
        $this->command->info('💰 Phase 5: Payments & Subscriptions');
        $this->call([
            PaymentSeeder::class,             // Payment Records
            PaymentTransactionSeeder::class,  // Detailed Transaction Data
            UserSubscriptionSeeder::class,    // Active/Expired Subscriptions
        ]);
        $this->command->newLine();

        // ========================================
        // PHASE 6: Interactions & Feedback
        // ========================================
        $this->command->info('⭐ Phase 6: Ratings & Reviews');
        $this->call([
            RatingReviewSeeder::class,        // Customer Reviews of Fundis
        ]);
        $this->command->newLine();

        // ========================================
        // PHASE 7: Communications
        // ========================================
        $this->command->info('🔔 Phase 7: Notifications');
        $this->call([
            NotificationSeeder::class,        // User Notifications
        ]);
        $this->command->newLine();

        // ========================================
        // PHASE 8: Audit & Monitoring
        // ========================================
        $this->command->info('📊 Phase 8: Audit & System Logs');
        $this->call([
            AuditLogSeeder::class,            // Comprehensive Audit Trail
        ]);
        $this->command->newLine();

        // ========================================
        // Summary
        // ========================================
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->displaySummary();
    }

    /**
     * Display seeding summary with statistics
     */
    private function displaySummary(): void
    {
        $this->command->table(
            ['Table', 'Approximate Records', 'Status'],
            [
                ['roles', '5 (customer, fundi, admin, moderator, support)', '✅'],
                ['permissions', '50+ granular permissions', '✅'],
                ['users', '12 test users across all roles', '✅'],
                ['user_sessions', '12-60 active/expired sessions', '✅'],
                ['categories', '12 job categories', '✅'],
                ['admin_settings', '1 system config', '✅'],
                ['payment_plans', '5 plans (free, subscription, pay-per-use)', '✅'],
                ['fundi_profiles', '6+ fundi profiles', '✅'],
                ['fundi_applications', '5 applications (pending/approved/rejected)', '✅'],
                ['fundi_application_sections', '25 multi-step sections', '✅'],
                ['job_postings', '10 jobs (various statuses)', '✅'],
                ['job_media', '20-50 job images/documents', '✅'],
                ['job_applications', '10-30 applications from fundis', '✅'],
                ['portfolio', '20-40 portfolio items', '✅'],
                ['portfolio_media', '60-320 portfolio images/videos', '✅'],
                ['work_submissions', '10-30 completed work submissions', '✅'],
                ['payments', '40-100 payment records', '✅'],
                ['payment_transactions', '40-100 transaction details', '✅'],
                ['user_subscriptions', '~30% users with subscriptions', '✅'],
                ['ratings_reviews', '8-20 customer reviews', '✅'],
                ['notifications', '60-180 user notifications', '✅'],
                ['audit_logs', '120-600 audit trail records', '✅'],
            ]
        );

        $this->command->newLine();
        $this->command->info('🔑 Test Credentials:');
        $this->command->table(
            ['Role', 'Phone', 'Password', 'Status'],
            [
                ['Admin', '0754289824', 'password123', 'Active'],
                ['Customer', '0654289825', 'password123', 'Active'],
                ['Fundi', '0654289827', 'password123', 'Active'],
                ['Fundi + Customer', '0754289832', 'password123', 'Active'],
                ['Moderator', '0754289834', 'password123', 'Active'],
            ]
        );

        $this->command->newLine();
        $this->command->info('📝 Notes:');
        $this->command->line('- All passwords are "password123"');
        $this->command->line('- NIDA numbers are 20 digits');
        $this->command->line('- Tokens are 6 digits for events');
        $this->command->line('- Locations are Tanzania coordinates');
        $this->command->line('- Payments use ZenoPay, M-Pesa, Tigo Pesa, Airtel Money');
        $this->command->line('- All data follows Tanzania market context');
        $this->command->newLine();
    }
}
