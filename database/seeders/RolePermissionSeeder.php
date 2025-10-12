<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create only Fundi system essential permissions
        $permissions = [
            // Job Management Permissions
            'create_jobs', 'edit_jobs', 'delete_jobs', 'view_jobs', 'apply_jobs', 'manage_jobs',
            'manage_job_applications', 'approve_job_applications', 'view_applications', 'view_job_feeds', 'search_jobs',
            
            // Fundi Application Permissions
            'create_applications', 'view_all_applications', 'manage_applications', 'delete_applications',
            
            // Portfolio Management Permissions
            'create_portfolio', 'edit_portfolio', 'delete_portfolio', 'view_portfolio',
            'approve_portfolio', 'reject_portfolio', 'manage_portfolio_media',
            
            // Work Approval Permissions
            'view_work_submissions', 'approve_work', 'reject_work',
            
            // User Management Permissions
            'view_users', 'edit_users', 'delete_users', 'manage_roles', 'ban_users', 'unban_users',
            'view_user_analytics',
            
            // Rating & Review Permissions
            'create_ratings', 'edit_ratings', 'delete_ratings', 'view_ratings', 'moderate_ratings',
            
            // Messaging Permissions
            'send_messages', 'view_messages', 'delete_messages', 'moderate_messages',
            
            // Notification Permissions
            'manage_notifications', 'send_notifications', 'view_notifications', 'delete_notifications',
            
            // Payment Permissions
            'view_payments', 'make_payments', 'process_payments', 'manage_payments', 'view_payment_analytics',
            
            // System Administration Permissions
            'admin_access', 'view_system', 'manage_system', 'view_analytics', 'manage_analytics',
            'view_audit_logs', 'manage_audit_logs', 'view_system_settings', 'manage_system_settings',
            'view_dashboard',
            
            // File Management Permissions
            'upload_files', 'delete_files',
            
            // Category Management Permissions
            'view_categories', 'create_categories', 'edit_categories', 'delete_categories', 'manage_categories',
            
            // Fundi Management Permissions
            'view_fundis', 'create_fundis', 'edit_fundis', 'delete_fundis', 'approve_fundis', 'reject_fundis', 'manage_fundis', 'view_fundi_analytics',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'api'
            ]);
        }

        // Create system roles with permissions
        $systemRoles = [
            [
                'name' => 'customer',
                'permissions' => [
                    // Customers can create and manage their own jobs, but NOT browse all jobs
                    'create_jobs', 'edit_jobs', 'delete_jobs', 'manage_job_applications', 
                    'approve_job_applications', 'view_applications', 'view_fundis', 'view_portfolio', 
                    'create_ratings', 'send_messages', 'view_messages', 'view_notifications', 'view_categories',
                    'view_payments', 'make_payments', 'upload_files', 'delete_files', 'create_applications', 'approve_work'
                    // Removed 'view_jobs' - customers should only see their own jobs via /jobs/my-jobs
                ]
            ],
            [
                'name' => 'fundi',
                'permissions' => [
                    'apply_jobs', 'view_jobs', 'view_job_feeds', 'search_jobs', 'create_portfolio',
                    'edit_portfolio', 'view_portfolio', 'create_ratings', 'send_messages', 'view_messages',
                    'view_notifications', 'view_categories', 'view_payments', 'make_payments', 'upload_files', 'delete_files'
                ]
            ],
            [
                'name' => 'admin',
                'permissions' => [
                    // All permissions including admin_access for admin panel routes
                    'admin_access', 'create_jobs', 'edit_jobs', 'delete_jobs', 'view_jobs', 'apply_jobs', 'manage_jobs',
                    'manage_job_applications', 'approve_job_applications', 'view_applications', 'view_job_feeds', 'search_jobs',
                    'create_applications', 'view_all_applications', 'manage_applications', 'delete_applications',
                    'create_portfolio', 'edit_portfolio', 'delete_portfolio', 'view_portfolio',
                    'approve_portfolio', 'reject_portfolio', 'manage_portfolio_media', 'approve_work', 'reject_work',
                    'view_users', 'edit_users', 'delete_users', 'manage_roles', 'ban_users', 'unban_users',
                    'view_user_analytics', 'create_ratings', 'edit_ratings', 'delete_ratings', 'view_ratings',
                    'moderate_ratings', 'send_messages', 'view_messages', 'delete_messages', 'moderate_messages',
                    'manage_notifications', 'send_notifications', 'view_notifications', 'delete_notifications',
                    'view_payments', 'make_payments', 'process_payments', 'manage_payments', 'view_payment_analytics',
                    'view_system', 'manage_system', 'view_analytics', 'manage_analytics',
                    'view_audit_logs', 'manage_audit_logs', 'view_system_settings', 'manage_system_settings',
                    'view_dashboard', 'upload_files', 'delete_files',
                    'view_categories', 'create_categories', 'edit_categories', 'delete_categories', 'manage_categories',
                    'view_fundis', 'create_fundis', 'edit_fundis', 'delete_fundis', 'approve_fundis', 'reject_fundis', 'manage_fundis', 'view_fundi_analytics'
                ]
            ]
        ];

        foreach ($systemRoles as $roleData) {
            $role = Role::firstOrCreate([
                'name' => $roleData['name'],
                'guard_name' => 'api'
            ]);

            // Assign permissions to role
            $permissions = Permission::whereIn('name', $roleData['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        // Create custom roles (optional)
        $customRoles = [
            [
                'name' => 'moderator',
                'permissions' => [
                    'view_users', 'ban_users', 'unban_users', 'moderate_ratings', 'moderate_messages',
                    'view_notifications', 'view_analytics', 'view_categories'
                ]
            ],
            [
                'name' => 'support',
                'permissions' => [
                    'view_users', 'view_jobs', 'view_portfolio', 'view_ratings', 'view_messages',
                    'view_notifications', 'view_categories'
                ]
            ]
        ];

        foreach ($customRoles as $roleData) {
            $role = Role::firstOrCreate([
                'name' => $roleData['name'],
                'guard_name' => 'api'
            ]);

            // Assign permissions to role
            $permissions = Permission::whereIn('name', $roleData['permissions'])->get();
            $role->syncPermissions($permissions);
        }
    }
}