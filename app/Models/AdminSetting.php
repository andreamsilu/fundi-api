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
}
