<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessModelConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_model',
        'allowed_client_roles',
        'allowed_provider_roles',
        'allowed_client_types',
        'allowed_provider_types',
        'supported_job_types',
        'supported_payment_methods',
        'supported_payment_schedules',
        'minimum_transaction_amount',
        'maximum_transaction_amount',
        'requires_contract',
        'requires_invoice',
        'requires_insurance',
        'requires_license',
        'requires_background_check',
        'additional_requirements',
        'platform_fee_percentage',
        'platform_fee_fixed',
        'minimum_fee',
        'maximum_fee',
        'enabled_features',
        'restricted_features',
        'description',
        'client_description',
        'provider_description',
        'is_active',
        'is_featured',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allowed_client_roles' => 'array',
        'allowed_provider_roles' => 'array',
        'allowed_client_types' => 'array',
        'allowed_provider_types' => 'array',
        'supported_job_types' => 'array',
        'supported_payment_methods' => 'array',
        'supported_payment_schedules' => 'array',
        'additional_requirements' => 'array',
        'enabled_features' => 'array',
        'restricted_features' => 'array',
        'requires_contract' => 'boolean',
        'requires_invoice' => 'boolean',
        'requires_insurance' => 'boolean',
        'requires_license' => 'boolean',
        'requires_background_check' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'minimum_transaction_amount' => 'decimal:2',
        'maximum_transaction_amount' => 'decimal:2',
        'platform_fee_percentage' => 'decimal:2',
        'platform_fee_fixed' => 'decimal:2',
        'minimum_fee' => 'decimal:2',
        'maximum_fee' => 'decimal:2',
    ];

    /**
     * Business model constants
     */
    const BUSINESS_MODEL_C2C = 'c2c';
    const BUSINESS_MODEL_B2C = 'b2c';
    const BUSINESS_MODEL_C2B = 'c2b';
    const BUSINESS_MODEL_B2B = 'b2b';

    /**
     * Get business model by type.
     */
    public static function getByModel(string $businessModel): ?self
    {
        return static::where('business_model', $businessModel)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if a user role can be a client in this business model.
     */
    public function canBeClient(string $role): bool
    {
        return in_array($role, $this->allowed_client_roles ?? []);
    }

    /**
     * Check if a user role can be a provider in this business model.
     */
    public function canBeProvider(string $role): bool
    {
        return in_array($role, $this->allowed_provider_roles ?? []);
    }

    /**
     * Check if a user type can be a client in this business model.
     */
    public function canBeClientType(string $userType): bool
    {
        return in_array($userType, $this->allowed_client_types ?? []);
    }

    /**
     * Check if a user type can be a provider in this business model.
     */
    public function canBeProviderType(string $userType): bool
    {
        return in_array($userType, $this->allowed_provider_types ?? []);
    }

    /**
     * Check if a job type is supported in this business model.
     */
    public function supportsJobType(string $jobType): bool
    {
        return in_array($jobType, $this->supported_job_types ?? []);
    }

    /**
     * Check if a payment method is supported in this business model.
     */
    public function supportsPaymentMethod(string $paymentMethod): bool
    {
        return in_array($paymentMethod, $this->supported_payment_methods ?? []);
    }

    /**
     * Check if a payment schedule is supported in this business model.
     */
    public function supportsPaymentSchedule(string $paymentSchedule): bool
    {
        return in_array($paymentSchedule, $this->supported_payment_schedules ?? []);
    }

    /**
     * Check if an amount is within the transaction limits.
     */
    public function isAmountWithinLimits(float $amount): bool
    {
        return $amount >= $this->minimum_transaction_amount && 
               $amount <= $this->maximum_transaction_amount;
    }

    /**
     * Calculate platform fee for a transaction amount.
     */
    public function calculatePlatformFee(float $amount): float
    {
        $percentageFee = ($amount * $this->platform_fee_percentage) / 100;
        $totalFee = $percentageFee + $this->platform_fee_fixed;
        
        return max($this->minimum_fee, min($this->maximum_fee, $totalFee));
    }

    /**
     * Get all active business model configs.
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->get();
    }

    /**
     * Get featured business model configs.
     */
    public static function getFeatured(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->where('is_featured', true)
            ->get();
    }

    /**
     * Scope a query to only include active configs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured configs.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
} 