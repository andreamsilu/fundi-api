<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\User;
use App\Models\Payment;
use App\Models\Job;
use App\Models\JobApplication;
use Carbon\Carbon;

class PaymentValidationService
{
    private static $settings = null;

    /**
     * Get admin settings (cached)
     */
    private static function getSettings(): AdminSetting
    {
        if (self::$settings === null) {
            self::$settings = AdminSetting::first() ?? new AdminSetting();
        }
        return self::$settings;
    }

    /**
     * Check if user can post a job (payment validation)
     */
    public static function canPostJob(User $user): array
    {
        $settings = self::getSettings();
        
        // If platform is in free mode, allow posting
        if ($settings->isFreeMode()) {
            return [
                'allowed' => true,
                'reason' => 'Free mode enabled',
                'fee_required' => false,
                'fee_amount' => 0
            ];
        }

        // Check if job posting fee is required
        if ($settings->isJobPostingFeeRequired()) {
            return [
                'allowed' => true,
                'reason' => 'Job posting fee required',
                'fee_required' => true,
                'fee_amount' => $settings->getJobPostingFee(),
                'payment_type' => 'job_posting'
            ];
        }

        // Check if subscription is required
        if ($settings->isSubscriptionRequired()) {
            $hasActiveSubscription = self::hasActiveSubscription($user);
            
            if (!$hasActiveSubscription) {
                return [
                    'allowed' => false,
                    'reason' => 'Active subscription required',
                    'fee_required' => true,
                    'fee_amount' => $settings->getSubscriptionFee(),
                    'payment_type' => 'subscription'
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => 'No payment required',
            'fee_required' => false,
            'fee_amount' => 0
        ];
    }

    /**
     * Check if user can apply for a job (payment validation)
     */
    public static function canApplyForJob(User $user, Job $job): array
    {
        $settings = self::getSettings();
        
        // If platform is in free mode, allow application
        if ($settings->isFreeMode()) {
            return [
                'allowed' => true,
                'reason' => 'Free mode enabled',
                'fee_required' => false,
                'fee_amount' => 0
            ];
        }

        // Check if job application fee is required
        if ($settings->isJobApplicationFeeRequired()) {
            return [
                'allowed' => true,
                'reason' => 'Job application fee required',
                'fee_required' => true,
                'fee_amount' => $settings->getJobApplicationFee(),
                'payment_type' => 'application_fee'
            ];
        }

        // Check if subscription is required
        if ($settings->isSubscriptionRequired()) {
            $hasActiveSubscription = self::hasActiveSubscription($user);
            
            if (!$hasActiveSubscription) {
                return [
                    'allowed' => false,
                    'reason' => 'Active subscription required',
                    'fee_required' => true,
                    'fee_amount' => $settings->getSubscriptionFee(),
                    'payment_type' => 'subscription'
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => 'No payment required',
            'fee_required' => false,
            'fee_amount' => 0
        ];
    }

    /**
     * Check if user has active subscription
     */
    public static function hasActiveSubscription(User $user): bool
    {
        $settings = self::getSettings();
        
        if (!$settings->isSubscriptionRequired()) {
            return true; // No subscription required
        }

        // Check for active subscription payment
        $subscriptionPayment = Payment::where('user_id', $user->id)
            ->where('payment_type', 'subscription')
            ->where('status', 'completed')
            ->where('created_at', '>=', self::getSubscriptionStartDate())
            ->first();

        return $subscriptionPayment !== null;
    }

    /**
     * Get subscription start date based on period
     */
    private static function getSubscriptionStartDate(): Carbon
    {
        $settings = self::getSettings();
        
        return match ($settings->subscription_period) {
            'yearly' => now()->subYear(),
            'monthly' => now()->subMonth(),
            default => now()->subMonth()
        };
    }

    /**
     * Get payment requirements for user
     */
    public static function getPaymentRequirements(User $user): array
    {
        $settings = self::getSettings();
        
        $requirements = [
            'platform_mode' => $settings->isFreeMode() ? 'free' : 'paid',
            'subscription_required' => $settings->isSubscriptionRequired(),
            'job_posting_fee_required' => $settings->isJobPostingFeeRequired(),
            'job_application_fee_required' => $settings->isJobApplicationFeeRequired(),
            'has_active_subscription' => self::hasActiveSubscription($user),
            'fees' => [
                'subscription' => $settings->getSubscriptionFee(),
                'job_posting' => $settings->getJobPostingFee(),
                'job_application' => $settings->getJobApplicationFee(),
            ]
        ];

        return $requirements;
    }

    /**
     * Validate payment before action
     */
    public static function validatePayment(User $user, string $action, array $context = []): array
    {
        return match ($action) {
            'post_job' => self::canPostJob($user),
            'apply_job' => self::canApplyForJob($user, $context['job'] ?? null),
            default => [
                'allowed' => true,
                'reason' => 'No validation required',
                'fee_required' => false,
                'fee_amount' => 0
            ]
        };
    }

    /**
     * Create payment record for required action
     */
    public static function createPaymentRecord(User $user, string $paymentType, float $amount, array $metadata = []): Payment
    {
        $pesapalReference = 'PAY_' . time() . '_' . $user->id . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        return Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_type' => $paymentType,
            'status' => 'pending',
            'pesapal_reference' => $pesapalReference,
            'metadata' => $metadata
        ]);
    }

    /**
     * Check if user needs to pay for specific action
     */
    public static function needsPayment(User $user, string $action): bool
    {
        $validation = self::validatePayment($user, $action);
        return $validation['fee_required'] ?? false;
    }

    /**
     * Get required payment amount for action
     */
    public static function getRequiredPaymentAmount(User $user, string $action): float
    {
        $validation = self::validatePayment($user, $action);
        return $validation['fee_amount'] ?? 0;
    }
}
