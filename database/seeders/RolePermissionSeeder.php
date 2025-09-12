<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create comprehensive permissions
        $permissions = [
            // Job permissions
            ['name' => 'create_jobs', 'display_name' => 'Create Jobs', 'description' => 'Create new job postings', 'category' => 'jobs'],
            ['name' => 'edit_jobs', 'display_name' => 'Edit Jobs', 'description' => 'Edit existing job postings', 'category' => 'jobs'],
            ['name' => 'delete_jobs', 'display_name' => 'Delete Jobs', 'description' => 'Delete job postings', 'category' => 'jobs'],
            ['name' => 'view_jobs', 'display_name' => 'View Jobs', 'description' => 'View job postings', 'category' => 'jobs'],
            ['name' => 'apply_jobs', 'display_name' => 'Apply for Jobs', 'description' => 'Apply for job postings', 'category' => 'jobs'],
            ['name' => 'manage_job_applications', 'display_name' => 'Manage Job Applications', 'description' => 'Manage job applications', 'category' => 'jobs'],
            ['name' => 'approve_job_applications', 'display_name' => 'Approve Job Applications', 'description' => 'Approve or reject job applications', 'category' => 'jobs'],
            ['name' => 'view_job_feeds', 'display_name' => 'View Job Feeds', 'description' => 'View job feed listings', 'category' => 'jobs'],
            ['name' => 'search_jobs', 'display_name' => 'Search Jobs', 'description' => 'Search and filter jobs', 'category' => 'jobs'],

            // Portfolio permissions
            ['name' => 'create_portfolio', 'display_name' => 'Create Portfolio', 'description' => 'Create portfolio items', 'category' => 'portfolio'],
            ['name' => 'edit_portfolio', 'display_name' => 'Edit Portfolio', 'description' => 'Edit portfolio items', 'category' => 'portfolio'],
            ['name' => 'delete_portfolio', 'display_name' => 'Delete Portfolio', 'description' => 'Delete portfolio items', 'category' => 'portfolio'],
            ['name' => 'view_portfolio', 'display_name' => 'View Portfolio', 'description' => 'View portfolio items', 'category' => 'portfolio'],
            ['name' => 'approve_portfolio', 'display_name' => 'Approve Portfolio', 'description' => 'Approve portfolio items', 'category' => 'portfolio'],
            ['name' => 'reject_portfolio', 'display_name' => 'Reject Portfolio', 'description' => 'Reject portfolio items', 'category' => 'portfolio'],
            ['name' => 'manage_portfolio_media', 'display_name' => 'Manage Portfolio Media', 'description' => 'Manage portfolio images and files', 'category' => 'portfolio'],

            // User permissions
            ['name' => 'view_users', 'display_name' => 'View Users', 'description' => 'View user profiles', 'category' => 'users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'description' => 'Edit user profiles', 'category' => 'users'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'description' => 'Delete users', 'category' => 'users'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles', 'description' => 'Manage user roles and permissions', 'category' => 'users'],
            ['name' => 'ban_users', 'display_name' => 'Ban Users', 'description' => 'Ban or suspend users', 'category' => 'users'],
            ['name' => 'unban_users', 'display_name' => 'Unban Users', 'description' => 'Unban or unsuspend users', 'category' => 'users'],
            ['name' => 'view_user_analytics', 'display_name' => 'View User Analytics', 'description' => 'View user statistics and analytics', 'category' => 'users'],

            // Rating permissions
            ['name' => 'create_ratings', 'display_name' => 'Create Ratings', 'description' => 'Create ratings and reviews', 'category' => 'ratings'],
            ['name' => 'edit_ratings', 'display_name' => 'Edit Ratings', 'description' => 'Edit ratings and reviews', 'category' => 'ratings'],
            ['name' => 'delete_ratings', 'display_name' => 'Delete Ratings', 'description' => 'Delete ratings and reviews', 'category' => 'ratings'],
            ['name' => 'view_ratings', 'display_name' => 'View Ratings', 'description' => 'View ratings and reviews', 'category' => 'ratings'],
            ['name' => 'moderate_ratings', 'display_name' => 'Moderate Ratings', 'description' => 'Moderate ratings and reviews', 'category' => 'ratings'],

            // Messaging permissions
            ['name' => 'send_messages', 'display_name' => 'Send Messages', 'description' => 'Send messages to other users', 'category' => 'messaging'],
            ['name' => 'view_messages', 'display_name' => 'View Messages', 'description' => 'View messages', 'category' => 'messaging'],
            ['name' => 'delete_messages', 'display_name' => 'Delete Messages', 'description' => 'Delete messages', 'category' => 'messaging'],
            ['name' => 'moderate_messages', 'display_name' => 'Moderate Messages', 'description' => 'Moderate user messages', 'category' => 'messaging'],

            // Notification permissions
            ['name' => 'manage_notifications', 'display_name' => 'Manage Notifications', 'description' => 'Manage notification settings', 'category' => 'notifications'],
            ['name' => 'send_notifications', 'display_name' => 'Send Notifications', 'description' => 'Send notifications to users', 'category' => 'notifications'],
            ['name' => 'view_notifications', 'display_name' => 'View Notifications', 'description' => 'View notification history', 'category' => 'notifications'],
            ['name' => 'delete_notifications', 'display_name' => 'Delete Notifications', 'description' => 'Delete notifications', 'category' => 'notifications'],

            // Payment permissions
            ['name' => 'process_payments', 'display_name' => 'Process Payments', 'description' => 'Process payment transactions', 'category' => 'payments'],
            ['name' => 'view_payments', 'display_name' => 'View Payments', 'description' => 'View payment history', 'category' => 'payments'],
            ['name' => 'refund_payments', 'display_name' => 'Refund Payments', 'description' => 'Process payment refunds', 'category' => 'payments'],
            ['name' => 'manage_payment_methods', 'display_name' => 'Manage Payment Methods', 'description' => 'Manage payment methods', 'category' => 'payments'],

            // Work Approval permissions
            ['name' => 'approve_work', 'display_name' => 'Approve Work', 'description' => 'Approve work submissions', 'category' => 'work_approval'],
            ['name' => 'reject_work', 'display_name' => 'Reject Work', 'description' => 'Reject work submissions', 'category' => 'work_approval'],
            ['name' => 'view_work_submissions', 'display_name' => 'View Work Submissions', 'description' => 'View work submissions', 'category' => 'work_approval'],
            ['name' => 'manage_work_approval', 'display_name' => 'Manage Work Approval', 'description' => 'Manage work approval process', 'category' => 'work_approval'],

            // Feed permissions
            ['name' => 'view_fundi_feeds', 'display_name' => 'View Fundi Feeds', 'description' => 'View fundi feed listings', 'category' => 'feeds'],
            ['name' => 'view_job_feeds', 'display_name' => 'View Job Feeds', 'description' => 'View job feed listings', 'category' => 'feeds'],
            ['name' => 'search_fundis', 'display_name' => 'Search Fundis', 'description' => 'Search and filter fundis', 'category' => 'feeds'],
            ['name' => 'view_nearby_fundis', 'display_name' => 'View Nearby Fundis', 'description' => 'View nearby fundis', 'category' => 'feeds'],

            // Fundi Application permissions
            ['name' => 'create_fundi_application', 'display_name' => 'Create Fundi Application', 'description' => 'Create fundi application', 'category' => 'fundi_application'],
            ['name' => 'view_fundi_applications', 'display_name' => 'View Fundi Applications', 'description' => 'View fundi applications', 'category' => 'fundi_application'],
            ['name' => 'approve_fundi_applications', 'display_name' => 'Approve Fundi Applications', 'description' => 'Approve fundi applications', 'category' => 'fundi_application'],
            ['name' => 'reject_fundi_applications', 'display_name' => 'Reject Fundi Applications', 'description' => 'Reject fundi applications', 'category' => 'fundi_application'],

            // Settings permissions
            ['name' => 'manage_settings', 'display_name' => 'Manage Settings', 'description' => 'Manage system settings', 'category' => 'settings'],
            ['name' => 'view_settings', 'display_name' => 'View Settings', 'description' => 'View system settings', 'category' => 'settings'],
            ['name' => 'export_data', 'display_name' => 'Export Data', 'description' => 'Export system data', 'category' => 'settings'],
            ['name' => 'import_data', 'display_name' => 'Import Data', 'description' => 'Import system data', 'category' => 'settings'],

            // Admin permissions
            ['name' => 'manage_system', 'display_name' => 'Manage System', 'description' => 'Manage system settings and configuration', 'category' => 'admin'],
            ['name' => 'view_analytics', 'display_name' => 'View Analytics', 'description' => 'View system analytics and reports', 'category' => 'admin'],
            ['name' => 'moderate_content', 'display_name' => 'Moderate Content', 'description' => 'Moderate user-generated content', 'category' => 'admin'],
            ['name' => 'manage_categories', 'display_name' => 'Manage Categories', 'description' => 'Manage job categories', 'category' => 'admin'],
            ['name' => 'view_system_logs', 'display_name' => 'View System Logs', 'description' => 'View system logs and audit trails', 'category' => 'admin'],
            ['name' => 'manage_backups', 'display_name' => 'Manage Backups', 'description' => 'Manage system backups', 'category' => 'admin'],
            ['name' => 'view_system_health', 'display_name' => 'View System Health', 'description' => 'View system health and monitoring', 'category' => 'admin'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                array_merge($permissionData, ['is_system_permission' => true])
            );
        }

        // Create default roles
        $roles = [
            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'description' => 'Can post jobs and hire fundis',
                'permissions' => [
                    'create_jobs', 'edit_jobs', 'delete_jobs', 'view_jobs',
                    'view_portfolio', 'approve_portfolio',
                    'create_ratings', 'edit_ratings', 'delete_ratings', 'view_ratings',
                    'send_messages', 'view_messages',
                    'manage_notifications', 'view_payments',
                ]
            ],
            [
                'name' => 'fundi',
                'display_name' => 'Fundi',
                'description' => 'Can apply for jobs and provide services',
                'permissions' => [
                    'view_jobs', 'apply_jobs',
                    'create_portfolio', 'edit_portfolio', 'delete_portfolio', 'view_portfolio',
                    'create_ratings', 'edit_ratings', 'delete_ratings', 'view_ratings',
                    'send_messages', 'view_messages',
                    'manage_notifications', 'view_payments',
                ]
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Can manage the platform and users',
                'permissions' => [
                    'create_jobs', 'edit_jobs', 'delete_jobs', 'view_jobs', 'apply_jobs', 'manage_job_applications',
                    'create_portfolio', 'edit_portfolio', 'delete_portfolio', 'view_portfolio', 'approve_portfolio',
                    'create_ratings', 'edit_ratings', 'delete_ratings', 'view_ratings',
                    'send_messages', 'view_messages',
                    'manage_notifications', 'send_notifications',
                    'process_payments', 'view_payments',
                    'view_users', 'edit_users', 'delete_users', 'manage_roles',
                    'manage_system', 'view_analytics', 'moderate_content', 'manage_categories',
                ]
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                array_merge($roleData, ['is_system_role' => true])
            );

            // Assign permissions to role
            $role->syncPermissions($permissions);
        }

        // Create some example custom roles
        $customRoles = [
            [
                'name' => 'moderator',
                'display_name' => 'Moderator',
                'description' => 'Can moderate content and manage users',
                'permissions' => [
                    'view_jobs', 'view_portfolio', 'view_ratings',
                    'view_users', 'edit_users',
                    'moderate_content', 'view_analytics',
                ]
            ],
            [
                'name' => 'premium_customer',
                'display_name' => 'Premium Customer',
                'description' => 'Customer with additional privileges',
                'permissions' => [
                    'create_jobs', 'edit_jobs', 'delete_jobs', 'view_jobs',
                    'view_portfolio', 'approve_portfolio',
                    'create_ratings', 'edit_ratings', 'delete_ratings', 'view_ratings',
                    'send_messages', 'view_messages',
                    'manage_notifications', 'view_payments',
                    'view_analytics', // Additional permission for premium users
                ]
            ],
        ];

        foreach ($customRoles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                array_merge($roleData, ['is_system_role' => false])
            );

            // Assign permissions to role
            $role->syncPermissions($permissions);
        }
    }
}