<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add indexes to optimize fundi feed queries
     */
    public function up(): void
    {
        // Indexes for users table (fundi queries)
        try {
            Schema::table('users', function (Blueprint $table) {
                // Index for role + status (common query pattern)
                if (Schema::hasColumn('users', 'role') && Schema::hasColumn('users', 'status')) {
                    $table->index(['role', 'status'], 'idx_users_role_status');
                }
                
                // Index for NIDA number (identity verification)
                if (Schema::hasColumn('users', 'nida_number')) {
                    $table->index('nida_number', 'idx_users_nida');
                }
            });
        } catch (\Exception $e) {
            \Log::info('Some user indexes may already exist: ' . $e->getMessage());
        }

        // Indexes for fundi_profiles table
        try {
            Schema::table('fundi_profiles', function (Blueprint $table) {
                // Index for verification status
                if (Schema::hasColumn('fundi_profiles', 'verification_status')) {
                    $table->index('verification_status', 'idx_fundi_profiles_verification');
                }
                
                // Index for availability
                if (Schema::hasColumn('fundi_profiles', 'is_available')) {
                    $table->index('is_available', 'idx_fundi_profiles_available');
                }
                
                // Index for hourly rate filtering
                if (Schema::hasColumn('fundi_profiles', 'hourly_rate')) {
                    $table->index('hourly_rate', 'idx_fundi_profiles_rate');
                }
                
                // Index for experience years
                if (Schema::hasColumn('fundi_profiles', 'experience_years')) {
                    $table->index('experience_years', 'idx_fundi_profiles_experience');
                }
                
                // Composite index for user_id + verification
                if (Schema::hasColumn('fundi_profiles', 'verification_status')) {
                    $table->index(['user_id', 'verification_status'], 'idx_fundi_profiles_user_verified');
                }
            });
        } catch (\Exception $e) {
            \Log::info('Some fundi_profiles indexes may already exist: ' . $e->getMessage());
        }

        // Indexes for portfolios table
        try {
            Schema::table('portfolios', function (Blueprint $table) {
                // Index for status alone
                if (Schema::hasColumn('portfolios', 'status')) {
                    $table->index('status', 'idx_portfolios_status');
                }
                
                // Index for visibility
                if (Schema::hasColumn('portfolios', 'visibility')) {
                    $table->index('visibility', 'idx_portfolios_visibility');
                }
                
                // Composite index for user_id + status + visibility
                if (Schema::hasColumn('portfolios', 'status') && Schema::hasColumn('portfolios', 'visibility')) {
                    $table->index(['user_id', 'status', 'visibility'], 'idx_portfolios_user_status_vis');
                }
            });
        } catch (\Exception $e) {
            \Log::info('Some portfolios indexes may already exist: ' . $e->getMessage());
        }

        // Indexes for job_applications table (for completed jobs count)
        try {
            Schema::table('job_applications', function (Blueprint $table) {
                // Composite index for fundi_id + status
                $table->index(['fundi_id', 'status'], 'idx_job_applications_fundi_status');
            });
        } catch (\Exception $e) {
            \Log::info('Some job_applications indexes may already exist: ' . $e->getMessage());
        }

        // Indexes for rating_reviews table (for average rating calculations)
        try {
            Schema::table('rating_reviews', function (Blueprint $table) {
                // Composite index for rated_user_id + rating
                $table->index(['rated_user_id', 'rating'], 'idx_rating_reviews_user_rating');
            });
        } catch (\Exception $e) {
            \Log::info('Some rating_reviews indexes may already exist: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from users table
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_role_status');
                if (Schema::hasColumn('users', 'nida_number')) {
                    $table->dropIndex('idx_users_nida');
                }
            });
        } catch (\Exception $e) {
            \Log::info('Error dropping users indexes: ' . $e->getMessage());
        }

        // Drop indexes from fundi_profiles table
        try {
            Schema::table('fundi_profiles', function (Blueprint $table) {
                $table->dropIndex('idx_fundi_profiles_verification');
                $table->dropIndex('idx_fundi_profiles_available');
                $table->dropIndex('idx_fundi_profiles_rate');
                $table->dropIndex('idx_fundi_profiles_experience');
                $table->dropIndex('idx_fundi_profiles_user_verified');
            });
        } catch (\Exception $e) {
            \Log::info('Error dropping fundi_profiles indexes: ' . $e->getMessage());
        }

        // Drop indexes from portfolios table
        try {
            Schema::table('portfolios', function (Blueprint $table) {
                $table->dropIndex('idx_portfolios_user_status_vis');
                $table->dropIndex('idx_portfolios_status');
                $table->dropIndex('idx_portfolios_visibility');
            });
        } catch (\Exception $e) {
            \Log::info('Error dropping portfolios indexes: ' . $e->getMessage());
        }

        // Drop indexes from job_applications table
        try {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->dropIndex('idx_job_applications_fundi_status');
            });
        } catch (\Exception $e) {
            \Log::info('Error dropping job_applications indexes: ' . $e->getMessage());
        }

        // Drop indexes from rating_reviews table
        try {
            Schema::table('rating_reviews', function (Blueprint $table) {
                $table->dropIndex('idx_rating_reviews_user_rating');
            });
        } catch (\Exception $e) {
            \Log::info('Error dropping rating_reviews indexes: ' . $e->getMessage());
        }
    }
};
