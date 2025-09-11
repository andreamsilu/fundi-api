<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $jobs = Job::all();
        $applications = JobApplication::all();

        if ($users->isEmpty()) {
            return;
        }

        $notificationTypes = [
            'job_application' => [
                'title' => 'New Job Application',
                'message' => 'You have received a new application for your job posting.',
                'icon' => 'work'
            ],
            'job_approved' => [
                'title' => 'Job Application Approved',
                'message' => 'Your job application has been approved by the customer.',
                'icon' => 'check_circle'
            ],
            'job_rejected' => [
                'title' => 'Job Application Rejected',
                'message' => 'Your job application was not selected for this job.',
                'icon' => 'cancel'
            ],
            'payment_received' => [
                'title' => 'Payment Received',
                'message' => 'You have received a payment for your completed work.',
                'icon' => 'payment'
            ],
            'rating_received' => [
                'title' => 'New Rating Received',
                'message' => 'A customer has rated your work. Check your profile to see the rating.',
                'icon' => 'star'
            ],
            'message_received' => [
                'title' => 'New Message',
                'message' => 'You have received a new message from a customer.',
                'icon' => 'message'
            ],
            'system' => [
                'title' => 'System Notification',
                'message' => 'Important system update or maintenance notice.',
                'icon' => 'info'
            ]
        ];

        // Create notifications for each user
        foreach ($users as $user) {
            $numNotifications = rand(5, 15);
            
            for ($i = 0; $i < $numNotifications; $i++) {
                $type = array_rand($notificationTypes);
                $notificationData = $notificationTypes[$type];
                
                // Determine if notification is read based on age
                $isRead = rand(1, 100) <= 70; // 70% chance of being read
                
                Notification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $notificationData['title'],
                    'message' => $this->getPersonalizedMessage($notificationData['message'], $user, $type),
                    'read_status' => $isRead,
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now()->subDays(rand(0, 15))
                ]);
            }
        }

        // Create specific notifications for job applications
        foreach ($applications as $application) {
            $job = $application->job;
            $fundi = $application->fundi;
            $customer = $job->customer;

            // Notification for customer about new application
            Notification::create([
                'user_id' => $customer->id,
                'type' => 'job_application',
                'title' => 'New Job Application',
                'message' => "You have received a new application for your job: {$job->title}",
                'read_status' => rand(1, 100) <= 60,
                'created_at' => $application->created_at,
                'updated_at' => $application->updated_at
            ]);

            // Notification for fundi about application status
            if ($application->status !== 'pending') {
                $statusMessage = $application->status === 'accepted' ? 'approved' : 'rejected';
                
                Notification::create([
                    'user_id' => $fundi->id,
                    'type' => "job_{$statusMessage}",
                    'title' => "Job Application {$statusMessage}",
                    'message' => "Your application for '{$job->title}' has been {$statusMessage}.",
                    'read_status' => rand(1, 100) <= 80,
                    'created_at' => $application->updated_at,
                    'updated_at' => $application->updated_at
                ]);
            }
        }
    }

    private function getPersonalizedMessage($baseMessage, $user, $type): string
    {
        $personalizations = [
            'job_application' => "Hi " . ($user->fundiProfile?->first_name ?? 'there') . ", {$baseMessage}",
            'job_approved' => "Great news! {$baseMessage}",
            'job_rejected' => "We're sorry, but {$baseMessage}",
            'payment_received' => "Congratulations! {$baseMessage}",
            'rating_received' => "Thank you! {$baseMessage}",
            'message_received' => "You have a new message. {$baseMessage}",
            'system' => "Important: {$baseMessage}"
        ];

        return $personalizations[$type] ?? $baseMessage;
    }

    private function getSenderName($type): ?string
    {
        $senders = [
            'job_application' => 'Fundi',
            'job_approved' => 'Customer',
            'job_rejected' => 'Customer',
            'payment_received' => 'System',
            'rating_received' => 'Customer',
            'message_received' => 'Customer',
            'system' => 'Admin'
        ];

        return $senders[$type] ?? null;
    }

    private function getSenderImageUrl($type): ?string
    {
        $images = [
            'job_application' => 'https://example.com/images/fundi-avatar.jpg',
            'job_approved' => 'https://example.com/images/customer-avatar.jpg',
            'job_rejected' => 'https://example.com/images/customer-avatar.jpg',
            'payment_received' => 'https://example.com/images/system-icon.jpg',
            'rating_received' => 'https://example.com/images/customer-avatar.jpg',
            'message_received' => 'https://example.com/images/customer-avatar.jpg',
            'system' => 'https://example.com/images/admin-icon.jpg'
        ];

        return $images[$type] ?? null;
    }

    private function getActionUrl($type, $user): ?string
    {
        $urls = [
            'job_application' => '/my-jobs',
            'job_approved' => '/my-applications',
            'job_rejected' => '/my-applications',
            'payment_received' => '/payments',
            'rating_received' => '/profile',
            'message_received' => '/messages',
            'system' => '/settings'
        ];

        return $urls[$type] ?? null;
    }

    private function getNotificationData($type, $user): array
    {
        $baseData = [
            'user_id' => $user->id,
            'timestamp' => now()->toISOString()
        ];

        $typeData = [
            'job_application' => ['priority' => 'high', 'category' => 'job'],
            'job_approved' => ['priority' => 'high', 'category' => 'job'],
            'job_rejected' => ['priority' => 'medium', 'category' => 'job'],
            'payment_received' => ['priority' => 'high', 'category' => 'payment'],
            'rating_received' => ['priority' => 'medium', 'category' => 'rating'],
            'message_received' => ['priority' => 'medium', 'category' => 'communication'],
            'system' => ['priority' => 'high', 'category' => 'system']
        ];

        return array_merge($baseData, $typeData[$type] ?? []);
    }
}
