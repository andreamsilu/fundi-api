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
     */
    public function canPerformAction(User $user, string $action): bool
    {
        $plan = $this->getUserPaymentPlan($user);
        
        // Free plan allows all actions by default
        if ($plan->isFree()) {
            return true;
        }
        
        // Check subscription plan limits
        if ($plan->isSubscription()) {
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
     */
    private function checkPayPerUseRequirements(User $user, PaymentPlan $plan, string $action): bool
    {
        // For pay-per-use, we need to check if user has sufficient balance
        // or if they can make payment for the action
        return true; // This would be implemented based on specific requirements
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
}
