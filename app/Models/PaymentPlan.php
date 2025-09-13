<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'price',
        'billing_cycle',
        'features',
        'limits',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'features' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Get the user subscriptions for this plan
     */
    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get the payment transactions for this plan
     */
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Check if this is a free plan
     */
    public function isFree(): bool
    {
        return $this->type === 'free';
    }

    /**
     * Check if this is a subscription plan
     */
    public function isSubscription(): bool
    {
        return $this->type === 'subscription';
    }

    /**
     * Check if this is a pay-per-use plan
     */
    public function isPayPerUse(): bool
    {
        return $this->type === 'pay_per_use';
    }

    /**
     * Get the default free plan
     */
    public static function getDefaultFreePlan()
    {
        return static::where('type', 'free')
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->first();
    }

    /**
     * Get active plans by type
     */
    public static function getActivePlansByType(string $type)
    {
        return static::where('type', $type)
                    ->where('is_active', true)
                    ->get();
    }
}
