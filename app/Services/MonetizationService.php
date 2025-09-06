<?php

namespace App\Services;

use App\Models\FundiCredits;
use App\Models\FundiSubscription;
use App\Models\Job;
use App\Models\JobApplicationFee;
use App\Models\PremiumJobBooster;
use App\Models\RevenueTracking;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonetizationService
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Calculate application fee based on job value.
     */
    public function calculateApplicationFee(Job $job): float
    {
        $jobValue = $this->getJobValue($job);
        
        if ($jobValue < 20000) {
            return 500; // < 20,000 TZS → 500 TZS
        } elseif ($jobValue <= 100000) {
            return 1000; // 20,000–100,000 TZS → 1,000 TZS
        } else {
            // > 100,000 TZS → 2,000–5,000 TZS (scaled based on value)
            $baseFee = 2000;
            $additionalFee = min(3000, ($jobValue - 100000) * 0.01); // 1% of excess, max 3000
            return $baseFee + $additionalFee;
        }
    }

    /**
     * Get the effective job value for fee calculation.
     */
    private function getJobValue(Job $job): float
    {
        if ($job->fixed_amount) {
            return (float) $job->fixed_amount;
        }
        
        if ($job->budget_max) {
            return (float) $job->budget_max;
        }
        
        if ($job->budget_min) {
            return (float) $job->budget_min;
        }
        
        // Default to minimum value if no budget specified
        return 10000;
    }

    /**
     * Check if a fundi can apply to a job.
     */
    public function canFundiApplyToJob(User $fundi, Job $job): array
    {
        $subscription = $this->getActiveSubscription($fundi);
        $credits = $this->getFundiCredits($fundi);
        $applicationFee = $this->calculateApplicationFee($job);
        
        // Check if job is boosted and customer has paid
        if ($job->is_featured) {
            $booster = PremiumJobBooster::where('job_id', $job->id)
                ->where('status', 'active')
                ->where('starts_at', '<=', now())
                ->where('expires_at', '>', now())
                ->first();
                
            if (!$booster) {
                return [
                    'can_apply' => false,
                    'reason' => 'Job is featured but not properly boosted',
                    'required_payment' => null
                ];
            }
        }
        
        // Check subscription applications first
        if ($subscription && $subscription->hasRemainingApplications()) {
            return [
                'can_apply' => true,
                'reason' => 'Using subscription application',
                'required_payment' => null,
                'payment_type' => 'subscription'
            ];
        }
        
        // Check if fundi has sufficient credits
        if ($credits && $credits->hasSufficientCredits($applicationFee)) {
            return [
                'can_apply' => true,
                'reason' => 'Using credit balance',
                'required_payment' => $applicationFee,
                'payment_type' => 'credits'
            ];
        }
        
        return [
            'can_apply' => false,
            'reason' => 'Insufficient subscription applications and credits',
            'required_payment' => $applicationFee,
            'payment_type' => 'credits'
        ];
    }

    /**
     * Process job application payment.
     */
    public function processApplicationPayment(User $fundi, Job $job): array
    {
        $canApply = $this->canFundiApplyToJob($fundi, $job);
        
        if (!$canApply['can_apply']) {
            return [
                'success' => false,
                'message' => $canApply['reason'],
                'application_fee' => null
            ];
        }
        
        $applicationFee = $this->calculateApplicationFee($job);
        
        return DB::transaction(function () use ($fundi, $job, $applicationFee, $canApply) {
            $subscription = $this->getActiveSubscription($fundi);
            $credits = $this->getFundiCredits($fundi);
            
            // Create application fee record
            $fee = JobApplicationFee::create([
                'job_id' => $job->id,
                'fundi_id' => $fundi->id,
                'fee_amount' => $applicationFee,
                'payment_type' => $canApply['payment_type'],
                'status' => 'pending'
            ]);
            
            if ($canApply['payment_type'] === 'subscription') {
                // Use subscription application
                $subscription->useApplication();
                $fee->update([
                    'subscription_id' => $subscription->id,
                    'status' => 'paid'
                ]);
                
                // Track revenue
                $this->trackRevenue('subscription', $fundi, $job, $applicationFee, 'Subscription application used');
                
            } else {
                // Use credits
                $creditTransaction = $credits->useCredits(
                    $applicationFee,
                    "Job application fee for job #{$job->id}",
                    $job->id
                );
                
                if (!$creditTransaction) {
                    throw new \Exception('Insufficient credits');
                }
                
                $fee->update([
                    'credit_transaction_id' => $creditTransaction->id,
                    'status' => 'paid'
                ]);
                
                // Track revenue
                $this->trackRevenue('credits', $fundi, $job, $applicationFee, 'Credit payment for job application');
            }
            
            return [
                'success' => true,
                'message' => 'Application payment processed successfully',
                'application_fee' => $fee
            ];
        });
    }

    /**
     * Calculate job boost fee based on business model.
     */
    public function calculateBoostFee(string $businessModel, string $boostType = 'featured'): float
    {
        switch ($businessModel) {
            case 'c2c':
                return 500; // 500 TZS
            case 'b2c':
                return 1000; // 500–1,000 TZS (using max)
            case 'b2b':
                return 10000; // 5,000–10,000 TZS/month (using max)
            case 'c2b':
                return 5000; // 2,000–5,000 TZS per premium job (using max)
            default:
                return 500;
        }
    }

    /**
     * Process job boost payment.
     */
    public function processJobBoost(User $customer, Job $job, string $boostType = 'featured'): array
    {
        $boostFee = $this->calculateBoostFee($job->business_model, $boostType);
        
        // Create payment
        $payment = $this->paymentService->createPayment(
            $customer,
            $boostFee,
            'TZS',
            'mobile_money',
            $job,
            [
                'boost_type' => $boostType,
                'business_model' => $job->business_model
            ]
        );
        
        // Initiate mobile money payment
        $paymentResult = $this->paymentService->initiateMobileMoney($payment, $customer->phone);
        
        if (isset($paymentResult['status']) && $paymentResult['status'] === 'initiated') {
            // Create booster record
            $booster = PremiumJobBooster::create([
                'job_id' => $job->id,
                'user_id' => $customer->id,
                'boost_type' => $boostType,
                'boost_fee' => $boostFee,
                'business_model' => $job->business_model,
                'starts_at' => now(),
                'expires_at' => now()->addDays(30), // 30 days boost
                'status' => 'active',
                'payment_id' => $payment->id,
            ]);
            
            // Mark job as featured
            $job->update(['is_featured' => true]);
            
            // Track revenue
            $this->trackRevenue('job_boost', $customer, $job, $boostFee, 'Job boost payment');
            
            return [
                'success' => true,
                'message' => 'Job boost payment initiated successfully',
                'payment' => $payment,
                'booster' => $booster
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to initiate payment',
            'payment' => $payment
        ];
    }

    /**
     * Purchase credits for a fundi.
     */
    public function purchaseCredits(User $fundi, float $amount, string $paymentMethod = 'mobile_money'): array
    {
        // Create payment
        $payment = $this->paymentService->createPayment(
            $fundi,
            $amount,
            'TZS',
            $paymentMethod,
            $fundi, // Payable is the fundi themselves
            ['type' => 'credit_purchase']
        );
        
        // Initiate mobile money payment
        $paymentResult = $this->paymentService->initiateMobileMoney($payment, $fundi->phone);
        
        if (isset($paymentResult['status']) && $paymentResult['status'] === 'initiated') {
            // Add credits to fundi account
            $credits = $this->getFundiCredits($fundi);
            $creditTransaction = $credits->addCredits($amount, 'Credit purchase via mobile money');
            
            // Link payment to credit transaction
            $creditTransaction->update(['payment_id' => $payment->id]);
            
            // Track revenue
            $this->trackRevenue('credits', $fundi, null, $amount, 'Credit purchase');
            
            return [
                'success' => true,
                'message' => 'Credit purchase initiated successfully',
                'payment' => $payment,
                'credit_transaction' => $creditTransaction
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to initiate credit purchase',
            'payment' => $payment
        ];
    }

    /**
     * Subscribe a fundi to a subscription tier.
     */
    public function subscribeFundi(User $fundi, SubscriptionTier $tier): array
    {
        $subscriptionFee = $tier->monthly_price_tzs;
        
        // Create payment
        $payment = $this->paymentService->createPayment(
            $fundi,
            $subscriptionFee,
            'TZS',
            'mobile_money',
            $fundi,
            ['type' => 'subscription', 'tier' => $tier->slug]
        );
        
        // Initiate mobile money payment
        $paymentResult = $this->paymentService->initiateMobileMoney($payment, $fundi->phone);
        
        if (isset($paymentResult['status']) && $paymentResult['status'] === 'initiated') {
            // Cancel any existing active subscription
            FundiSubscription::where('user_id', $fundi->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);
            
            // Create new subscription
            $subscription = FundiSubscription::create([
                'user_id' => $fundi->id,
                'subscription_tier_id' => $tier->id,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
                'remaining_applications' => $tier->included_job_applications,
                'last_reset_at' => now(),
            ]);
            
            // Track revenue
            $this->trackRevenue('subscription', $fundi, null, $subscriptionFee, 'Monthly subscription payment');
            
            return [
                'success' => true,
                'message' => 'Subscription initiated successfully',
                'payment' => $payment,
                'subscription' => $subscription
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to initiate subscription payment',
            'payment' => $payment
        ];
    }

    /**
     * Get active subscription for a fundi.
     */
    public function getActiveSubscription(User $fundi): ?FundiSubscription
    {
        return FundiSubscription::where('user_id', $fundi->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Get or create fundi credits.
     */
    public function getFundiCredits(User $fundi): FundiCredits
    {
        return FundiCredits::firstOrCreate(
            ['user_id' => $fundi->id],
            [
                'balance' => 0,
                'total_purchased' => 0,
                'total_used' => 0,
            ]
        );
    }

    /**
     * Track revenue for reporting.
     */
    public function trackRevenue(
        string $revenueType,
        User $user,
        ?Job $job,
        float $amount,
        string $description,
        ?string $businessModel = null
    ): RevenueTracking {
        return RevenueTracking::create([
            'revenue_type' => $revenueType,
            'user_id' => $user->id,
            'job_id' => $job?->id,
            'business_model' => $businessModel ?? $job?->business_model,
            'amount' => $amount,
            'currency' => 'TZS',
            'description' => $description,
            'revenue_date' => now()->toDateString(),
        ]);
    }

    /**
     * Get revenue statistics for admin dashboard.
     */
    public function getRevenueStats(string $period = 'month'): array
    {
        $startDate = match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };
        
        $endDate = now();
        
        $revenue = RevenueTracking::whereBetween('revenue_date', [$startDate, $endDate])
            ->selectRaw('
                revenue_type,
                business_model,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            ')
            ->groupBy('revenue_type', 'business_model')
            ->get();
        
        $totalRevenue = $revenue->sum('total_amount');
        $subscriptionRevenue = $revenue->where('revenue_type', 'subscription')->sum('total_amount');
        $creditRevenue = $revenue->where('revenue_type', 'credits')->sum('total_amount');
        $boostRevenue = $revenue->where('revenue_type', 'job_boost')->sum('total_amount');
        
        return [
            'period' => $period,
            'total_revenue' => $totalRevenue,
            'subscription_revenue' => $subscriptionRevenue,
            'credit_revenue' => $creditRevenue,
            'boost_revenue' => $boostRevenue,
            'breakdown' => $revenue->toArray(),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }
}
