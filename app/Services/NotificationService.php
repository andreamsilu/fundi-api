<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification to a user
     *
     * @param User $user
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $data
     * @param Model|null $notifiable
     * @return Notification
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?Model $notifiable = null
    ): Notification {
        try {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'notifiable_type' => $notifiable ? get_class($notifiable) : null,
                'notifiable_id' => $notifiable ? $notifiable->id : null,
            ]);

            // Send push notification if user has a device token
            if ($user->device_token) {
                $this->sendPushNotification($user, $title, $message, $data);
            }

            // Send email notification
            $this->sendEmailNotification($user, $title, $message);

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to send notification: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send a push notification
     *
     * @param User $user
     * @param string $title
     * @param string $message
     * @param array $data
     * @return void
     */
    protected function sendPushNotification(User $user, string $title, string $message, array $data = []): void
    {
        // TODO: Implement push notification service (Firebase, OneSignal, etc.)
        // This is a placeholder for the actual implementation
        Log::info('Push notification would be sent', [
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Send an email notification
     *
     * @param User $user
     * @param string $title
     * @param string $message
     * @return void
     */
    protected function sendEmailNotification(User $user, string $title, string $message): void
    {
        // TODO: Implement email notification service
        // This is a placeholder for the actual implementation
        Log::info('Email notification would be sent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'title' => $title,
            'message' => $message
        ]);
    }

    /**
     * Send a booking notification
     *
     * @param User $user
     * @param string $action
     * @param Model $booking
     * @return Notification
     */
    public function sendBookingNotification(User $user, string $action, Model $booking): Notification
    {
        $titles = [
            'created' => 'New Booking Request',
            'accepted' => 'Booking Accepted',
            'rejected' => 'Booking Rejected',
            'completed' => 'Booking Completed',
            'cancelled' => 'Booking Cancelled'
        ];

        $messages = [
            'created' => 'You have a new booking request',
            'accepted' => 'Your booking request has been accepted',
            'rejected' => 'Your booking request has been rejected',
            'completed' => 'Your booking has been marked as completed',
            'cancelled' => 'Your booking has been cancelled'
        ];

        return $this->send(
            $user,
            'booking.' . $action,
            $titles[$action] ?? 'Booking Update',
            $messages[$action] ?? 'Your booking status has been updated',
            ['booking_id' => $booking->id],
            $booking
        );
    }

    /**
     * Send a review notification
     *
     * @param User $user
     * @param Model $review
     * @return Notification
     */
    public function sendReviewNotification(User $user, Model $review): Notification
    {
        return $this->send(
            $user,
            'review.created',
            'New Review Received',
            'You have received a new review',
            ['review_id' => $review->id],
            $review
        );
    }

    /**
     * Send a job notification
     *
     * @param User $user
     * @param string $action
     * @param Model $job
     * @return Notification
     */
    public function sendJobNotification(User $user, string $action, Model $job): Notification
    {
        $titles = [
            'created' => 'New Job Posted',
            'assigned' => 'Job Assigned',
            'completed' => 'Job Completed',
            'cancelled' => 'Job Cancelled'
        ];

        $messages = [
            'created' => 'A new job has been posted',
            'assigned' => 'You have been assigned to a job',
            'completed' => 'Your job has been marked as completed',
            'cancelled' => 'Your job has been cancelled'
        ];

        return $this->send(
            $user,
            'job.' . $action,
            $titles[$action] ?? 'Job Update',
            $messages[$action] ?? 'Your job status has been updated',
            ['job_id' => $job->id],
            $job
        );
    }
} 