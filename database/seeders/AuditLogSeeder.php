<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        $actions = [
            'user.login' => 'User logged in',
            'user.logout' => 'User logged out',
            'user.register' => 'User registered',
            'user.profile.update' => 'User profile updated',
            'job.create' => 'Job created',
            'job.update' => 'Job updated',
            'job.delete' => 'Job deleted',
            'job.application.create' => 'Job application created',
            'job.application.update' => 'Job application updated',
            'portfolio.create' => 'Portfolio item created',
            'portfolio.update' => 'Portfolio item updated',
            'portfolio.delete' => 'Portfolio item deleted',
            'payment.create' => 'Payment created',
            'payment.update' => 'Payment updated',
            'rating.create' => 'Rating created',
            'rating.update' => 'Rating updated',
            'notification.create' => 'Notification created',
            'notification.read' => 'Notification marked as read',
            'admin.user.update' => 'Admin updated user',
            'admin.user.delete' => 'Admin deleted user',
            'admin.job.update' => 'Admin updated job',
            'admin.job.delete' => 'Admin deleted job',
            'admin.settings.update' => 'Admin updated settings'
        ];

        $ipAddresses = [
            '192.168.1.100', '192.168.1.101', '192.168.1.102', '192.168.1.103',
            '10.0.0.50', '10.0.0.51', '10.0.0.52', '10.0.0.53',
            '172.16.0.10', '172.16.0.11', '172.16.0.12', '172.16.0.13',
            '203.0.113.1', '203.0.113.2', '203.0.113.3', '203.0.113.4'
        ];

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:89.0) Gecko/20100101 Firefox/89.0'
        ];

        $statuses = ['success', 'failed', 'pending'];
        $severities = ['low', 'medium', 'high', 'critical'];

        // Create audit logs for each user
        foreach ($users as $user) {
            $numLogs = rand(10, 50); // Each user gets 10-50 audit logs
            
            for ($i = 0; $i < $numLogs; $i++) {
                $action = array_rand($actions);
                $description = $actions[$action];
                $status = $statuses[array_rand($statuses)];
                $severity = $severities[array_rand($severities)];
                
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => $action,
                    'resource_type' => $this->getResourceType($action),
                    'resource_id' => $this->getResourceId($action, $user),
                    'ip_address' => $ipAddresses[array_rand($ipAddresses)],
                    'user_agent' => $userAgents[array_rand($userAgents)],
                    'status' => $status,
                    'metadata' => $this->getMetadata($action, $user),
                    'created_at' => now()->subDays(rand(0, 90)), // Within last 3 months
                    'updated_at' => now()->subDays(rand(0, 30))
                ]);
            }
        }

        // Create some system-level audit logs
        $systemActions = [
            'system.startup' => 'System started',
            'system.shutdown' => 'System shutdown',
            'system.maintenance' => 'System maintenance performed',
            'system.backup' => 'System backup completed',
            'system.update' => 'System updated',
            'database.migration' => 'Database migration executed',
            'cache.clear' => 'Cache cleared',
            'queue.process' => 'Queue processed'
        ];

        foreach ($systemActions as $action => $description) {
            AuditLog::create([
                'user_id' => null, // System action
                'action' => $action,
                'resource_type' => 'System',
                'resource_id' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'System',
                'status' => 'success',
                'metadata' => [
                    'system_action' => true,
                    'timestamp' => now()->toISOString()
                ],
                'created_at' => now()->subDays(rand(0, 30)),
                'updated_at' => now()->subDays(rand(0, 15))
            ]);
        }
    }

    private function getMetadata($action, $user): array
    {
        $baseMetadata = [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'timestamp' => now()->toISOString()
        ];

        $actionMetadata = [
            'user.login' => ['login_method' => 'phone', 'session_duration' => rand(30, 1440)],
            'user.logout' => ['logout_reason' => 'user_initiated', 'session_duration' => rand(30, 1440)],
            'user.register' => ['registration_method' => 'phone', 'verification_status' => 'pending'],
            'user.profile.update' => ['fields_updated' => ['first_name', 'last_name', 'bio']],
            'job.create' => ['job_category' => 'General', 'budget_range' => '10000-50000'],
            'job.update' => ['fields_updated' => ['title', 'description', 'budget']],
            'job.delete' => ['deletion_reason' => 'user_request'],
            'job.application.create' => ['application_status' => 'pending', 'proposed_budget' => rand(10000, 100000)],
            'job.application.update' => ['status_change' => 'accepted'],
            'portfolio.create' => ['portfolio_category' => 'General', 'skills_count' => rand(2, 5)],
            'portfolio.update' => ['fields_updated' => ['title', 'description', 'skills']],
            'portfolio.delete' => ['deletion_reason' => 'user_request'],
            'payment.create' => ['payment_method' => 'mobile_money', 'amount' => rand(1000, 100000)],
            'payment.update' => ['status_change' => 'completed'],
            'rating.create' => ['rating_value' => rand(1, 5), 'is_verified' => true],
            'rating.update' => ['rating_change' => 'increased'],
            'notification.create' => ['notification_type' => 'job_application', 'priority' => 'high'],
            'notification.read' => ['read_timestamp' => now()->toISOString()],
            'admin.user.update' => ['admin_action' => true, 'target_user_id' => rand(1, 100)],
            'admin.user.delete' => ['admin_action' => true, 'deletion_reason' => 'policy_violation'],
            'admin.job.update' => ['admin_action' => true, 'moderation_reason' => 'content_review'],
            'admin.job.delete' => ['admin_action' => true, 'deletion_reason' => 'policy_violation'],
            'admin.settings.update' => ['admin_action' => true, 'settings_updated' => ['payment_enabled', 'subscription_fee']]
        ];

        return array_merge($baseMetadata, $actionMetadata[$action] ?? []);
    }

    private function getResourceType($action): string
    {
        $resourceTypes = [
            'user.login' => 'User',
            'user.logout' => 'User',
            'user.register' => 'User',
            'user.profile.update' => 'User',
            'job.create' => 'Job',
            'job.update' => 'Job',
            'job.delete' => 'Job',
            'job.application.create' => 'JobApplication',
            'job.application.update' => 'JobApplication',
            'portfolio.create' => 'Portfolio',
            'portfolio.update' => 'Portfolio',
            'portfolio.delete' => 'Portfolio',
            'payment.create' => 'Payment',
            'payment.update' => 'Payment',
            'rating.create' => 'RatingReview',
            'rating.update' => 'RatingReview',
            'notification.create' => 'Notification',
            'notification.read' => 'Notification',
            'admin.user.update' => 'User',
            'admin.user.delete' => 'User',
            'admin.job.update' => 'Job',
            'admin.job.delete' => 'Job',
            'admin.settings.update' => 'AdminSetting',
            'system.startup' => 'System',
            'system.shutdown' => 'System',
            'system.maintenance' => 'System',
            'system.backup' => 'System',
            'system.update' => 'System',
            'database.migration' => 'System',
            'cache.clear' => 'System',
            'queue.process' => 'System'
        ];

        return $resourceTypes[$action] ?? 'Unknown';
    }

    private function getResourceId($action, $user): ?int
    {
        // For user-related actions, return the user ID
        if (strpos($action, 'user.') === 0) {
            return $user->id;
        }

        // For other actions, return a random ID or null
        $randomIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        return $randomIds[array_rand($randomIds)];
    }
}
