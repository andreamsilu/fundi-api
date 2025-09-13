<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_plan_id',
        'status',
        'starts_at',
        'expires_at',
        'cancelled_at',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment plan for this subscription
     */
    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->expires_at && 
               $this->expires_at->isFuture();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || $this->cancelled_at !== null;
    }

    /**
     * Get days remaining until expiration
     */
    public function getDaysRemaining(): int
    {
        if (!$this->expires_at) {
            return 0;
        }
        
        return max(0, Carbon::now()->diffInDays($this->expires_at, false));
    }

    /**
     * Extend subscription by given days
     */
    public function extend(int $days): void
    {
        if ($this->expires_at) {
            $this->expires_at = $this->expires_at->addDays($days);
        } else {
            $this->expires_at = Carbon::now()->addDays($days);
        }
        $this->save();
    }

    /**
     * Cancel subscription
     */
    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->cancelled_at = Carbon::now();
        $this->save();
    }
}
