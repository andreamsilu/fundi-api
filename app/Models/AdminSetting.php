<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    protected $fillable = [
        'payments_enabled',
        'payment_model',
        'subscription_enabled',
        'subscription_fee',
        'subscription_period',
        'job_application_fee_enabled',
        'job_application_fee',
        'job_posting_fee_enabled',
        'job_posting_fee',
        'premium_profile_fee',
        'featured_job_fee',
        'subscription_monthly_fee',
        'subscription_yearly_fee',
        'platform_commission_percentage',
        'application_fee', // Legacy
        'job_post_fee', // Legacy
    ];

    protected $casts = [
        'payments_enabled' => 'boolean',
        'subscription_enabled' => 'boolean',
        'subscription_fee' => 'decimal:2',
        'job_application_fee_enabled' => 'boolean',
        'job_application_fee' => 'decimal:2',
        'job_posting_fee_enabled' => 'boolean',
        'job_posting_fee' => 'decimal:2',
        'premium_profile_fee' => 'decimal:2',
        'featured_job_fee' => 'decimal:2',
        'subscription_monthly_fee' => 'decimal:2',
        'subscription_yearly_fee' => 'decimal:2',
        'platform_commission_percentage' => 'decimal:2',
        'application_fee' => 'decimal:2', // Legacy
        'job_post_fee' => 'decimal:2', // Legacy
    ];

    /**
     * Check if platform is in free mode
     */
    public function isFreeMode(): bool
    {
        return !$this->payments_enabled || 
               (!$this->subscription_enabled && 
                !$this->job_application_fee_enabled && 
                !$this->job_posting_fee_enabled);
    }

    /**
     * Check if subscription is required
     */
    public function isSubscriptionRequired(): bool
    {
        return $this->payments_enabled && $this->subscription_enabled;
    }

    /**
     * Check if job application fee is required
     */
    public function isJobApplicationFeeRequired(): bool
    {
        return $this->payments_enabled && $this->job_application_fee_enabled;
    }

    /**
     * Check if job posting fee is required
     */
    public function isJobPostingFeeRequired(): bool
    {
        return $this->payments_enabled && $this->job_posting_fee_enabled;
    }

    /**
     * Get subscription fee amount
     */
    public function getSubscriptionFee(): float
    {
        return $this->subscription_fee ?? 0;
    }

    /**
     * Get job application fee amount
     */
    public function getJobApplicationFee(): float
    {
        return $this->job_application_fee ?? 0;
    }

    /**
     * Get job posting fee amount
     */
    public function getJobPostingFee(): float
    {
        return $this->job_posting_fee ?? 0;
    }

    /**
     * Get premium profile fee amount
     */
    public function getPremiumProfileFee(): float
    {
        return $this->premium_profile_fee ?? 500;
    }

    /**
     * Get featured job fee amount
     */
    public function getFeaturedJobFee(): float
    {
        return $this->featured_job_fee ?? 2000;
    }

    /**
     * Get monthly subscription fee amount
     */
    public function getMonthlySubscriptionFee(): float
    {
        return $this->subscription_monthly_fee ?? 5000;
    }

    /**
     * Get yearly subscription fee amount
     */
    public function getYearlySubscriptionFee(): float
    {
        return $this->subscription_yearly_fee ?? 50000;
    }

    /**
     * Get platform commission percentage
     */
    public function getPlatformCommission(): float
    {
        return $this->platform_commission_percentage ?? 10;
    }

    /**
     * Get all pricing as array
     */
    public function getAllPricing(): array
    {
        return [
            'job_application_fee' => $this->getJobApplicationFee(),
            'job_posting_fee' => $this->getJobPostingFee(),
            'premium_profile_fee' => $this->getPremiumProfileFee(),
            'featured_job_fee' => $this->getFeaturedJobFee(),
            'subscription_monthly_fee' => $this->getMonthlySubscriptionFee(),
            'subscription_yearly_fee' => $this->getYearlySubscriptionFee(),
            'platform_commission_percentage' => $this->getPlatformCommission(),
        ];
    }

    /**
     * Get singleton instance (for easy global access)
     */
    public static function getSingleton(): self
    {
        $settings = self::first();
        
        if (!$settings) {
            // Create default settings if none exist
            $settings = self::create([
                'payments_enabled' => false,
                'payment_model' => 'free',
                'subscription_enabled' => false,
                'job_application_fee_enabled' => false,
                'job_posting_fee_enabled' => false,
                'job_application_fee' => 200,
                'job_posting_fee' => 1000,
                'premium_profile_fee' => 500,
                'featured_job_fee' => 2000,
                'subscription_monthly_fee' => 5000,
                'subscription_yearly_fee' => 50000,
                'platform_commission_percentage' => 10,
            ]);
        }
        
        return $settings;
    }
}
