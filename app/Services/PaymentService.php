<?php

namespace App\Services;

use App\Models\User;
use App\Models\PaymentPlan;
use App\Models\UserSubscription;
use App\Models\PaymentTransaction;
use Carbon\Carbon;

class PaymentService
{
    /**
     * Get user's current active subscription
     */
    public function getUserActiveSubscription(User $user): ?UserSubscription
    {
        return UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->with('paymentPlan')
            ->first();
    }

    /**
     * Get user's current payment plan
     */
    public function getUserPaymentPlan(User $user): PaymentPlan
    {
        $subscription = $this->getUserActiveSubscription($user);
        
        if ($subscription) {
            return $subscription->paymentPlan;
        }
        
        // Return default free plan if no active subscription
        return PaymentPlan::getDefaultFreePlan() ?? $this->createDefaultFreePlan();
    }

    /**
     * Check if user can perform action based on payment plan
     * 
     * @param User $user
     * @param string $action Possible values: post_job, apply_job, browse_fundis, message_fundi
     * @return bool True if user can perform the action
     */
    public function canPerformAction(User $user, string $action): bool
    {
        $plan = $this->getUserPaymentPlan($user);
        
        // Get active subscription
        $subscription = $this->getUserActiveSubscription($user);
        
        // Free plan allows all actions by default
        if ($plan->isFree()) {
            return true;
        }
        
        // Check subscription plan limits (must have ACTIVE subscription)
        if ($plan->isSubscription()) {
            // Subscription must be active
            if (!$subscription || !$subscription->isActive()) {
                return false; // No active subscription - deny access
            }
            return $this->checkSubscriptionLimits($user, $plan, $action);
        }
        
        // Check pay-per-use requirements
        if ($plan->isPayPerUse()) {
            return $this->checkPayPerUseRequirements($user, $plan, $action);
        }
        
        return false;
    }

    /**
     * Check subscription plan limits
     */
    private function checkSubscriptionLimits(User $user, PaymentPlan $plan, string $action): bool
    {
        $limits = $plan->limits ?? [];
        
        switch ($action) {
            case 'post_job':
                $monthlyJobs = $this->getUserMonthlyJobCount($user);
                $limit = $limits['monthly_jobs'] ?? null;
                return $limit === null || $monthlyJobs < $limit;
                
            case 'apply_job':
                $monthlyApplications = $this->getUserMonthlyApplicationCount($user);
                $limit = $limits['monthly_applications'] ?? null;
                return $limit === null || $monthlyApplications < $limit;
                
            case 'browse_fundis':
                return true; // Always allowed
                
            default:
                return true;
        }
    }

    /**
     * Check pay-per-use requirements
     * User must have paid for the specific action
     */
    private function checkPayPerUseRequirements(User $user, PaymentPlan $plan, string $action): bool
    {
        $limits = $plan->limits ?? [];
        
        switch ($action) {
            case 'post_job':
                // Check if plan allows job posting
                $jobPostingCost = $limits['job_posting_cost'] ?? 0;
                if ($jobPostingCost == 0) {
                    return false; // This plan doesn't support job posting
                }
                // User must pay per job - they can post but will be charged
                return true;
                
            case 'apply_job':
                // Check if plan allows job applications
                $applicationCost = $limits['application_cost'] ?? 0;
                if ($applicationCost == 0) {
                    return false; // This plan doesn't support applications
                }
                // User must pay per application - they can apply but will be charged
                return true;
                
            case 'browse_fundis':
                // Browsing fundis is typically free even for pay-per-use
                return true;
                
            case 'message_fundi':
                // Messaging requires payment or subscription
                return false; // Pay-per-use plans don't support messaging
                
            default:
                return false;
        }
    }

    /**
     * Create subscription for user
     */
    public function createSubscription(User $user, PaymentPlan $plan, int $durationDays = 30): UserSubscription
    {
        // Cancel any existing active subscription
        $this->cancelUserSubscriptions($user);
        
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'payment_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays($durationDays),
            'metadata' => [
                'created_by' => 'system',
                'duration_days' => $durationDays,
            ],
        ]);
        
        return $subscription;
    }

    /**
     * Cancel user's active subscriptions
     */
    public function cancelUserSubscriptions(User $user): void
    {
        UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
    }

    /**
     * Create payment transaction
     */
    public function createTransaction(
        User $user,
        PaymentPlan $plan,
        string $type,
        float $amount,
        ?string $referenceId = null,
        ?string $description = null
    ): PaymentTransaction {
        return PaymentTransaction::create([
            'user_id' => $user->id,
            'payment_plan_id' => $plan->id,
            'transaction_type' => $type,
            'reference_id' => $referenceId,
            'amount' => $amount,
            'currency' => 'TZS',
            'status' => 'pending',
            'description' => $description,
        ]);
    }

    /**
     * Get user's monthly job count
     */
    private function getUserMonthlyJobCount(User $user): int
    {
        return \App\Models\Job::where('customer_id', $user->id)
            ->where('created_at', '>=', now()->subMonth())
            ->count();
    }

    /**
     * Get user's monthly application count
     */
    private function getUserMonthlyApplicationCount(User $user): int
    {
        return \App\Models\JobApplication::where('fundi_id', $user->id)
            ->where('created_at', '>=', now()->subMonth())
            ->count();
    }

    /**
     * Create default free plan
     */
    private function createDefaultFreePlan(): PaymentPlan
    {
        return PaymentPlan::create([
            'name' => 'Free Plan',
            'type' => 'free',
            'description' => 'Unlimited access to all platform features',
            'price' => 0.00,
            'features' => [
                'unlimited_job_posting',
                'unlimited_job_applications',
                'unlimited_fundi_browsing',
                'unlimited_messaging',
                'basic_support',
            ],
            'limits' => [
                'monthly_jobs' => null, // Unlimited
                'monthly_applications' => null, // Unlimited
            ],
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /**
     * Get all available payment plans
     */
    public function getAvailablePlans(): array
    {
        return PaymentPlan::where('is_active', true)
            ->orderBy('price')
            ->get()
            ->toArray();
    }

    /**
     * Get user's payment history
     */
    public function getUserPaymentHistory(User $user, int $limit = 50): array
    {
        return PaymentTransaction::where('user_id', $user->id)
            ->with('paymentPlan')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Process pay-per-use payment
     */
    public function processPayPerUse(User $user, string $feature): array
    {
        $plan = $this->getUserPaymentPlan($user);

        // Check if user's plan supports pay-per-use
        if (!$plan->isPayPerUse()) {
            return [
                'success' => false,
                'message' => 'Your current plan does not support pay-per-use',
            ];
        }

        // Get feature cost
        $costs = [
            'post_job' => 5000, // TZS 5,000 per job
            'apply_job' => 2000, // TZS 2,000 per application
        ];

        $amount = $costs[$feature] ?? 0;

        if ($amount === 0) {
            return [
                'success' => false,
                'message' => 'Invalid feature for pay-per-use',
            ];
        }

        // Create transaction
        $transaction = $this->createTransaction(
            $user,
            $plan,
            'pay_per_use',
            $amount,
            $feature,
            "Pay-per-use: {$feature}"
        );

        return [
            'success' => true,
            'message' => 'Pay-per-use transaction created',
            'data' => $transaction,
        ];
    }
}
