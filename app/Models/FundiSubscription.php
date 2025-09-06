<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class FundiSubscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subscription_tier_id',
        'status',
        'starts_at',
        'expires_at',
        'remaining_applications',
        'last_reset_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'remaining_applications' => 'integer',
        'last_reset_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription tier.
     */
    public function subscriptionTier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class);
    }

    /**
     * Get the job application fees for this subscription.
     */
    public function jobApplicationFees(): HasMany
    {
        return $this->hasMany(JobApplicationFee::class);
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    /**
     * Check if the subscription has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the fundi has remaining applications.
     */
    public function hasRemainingApplications(): bool
    {
        return $this->remaining_applications > 0;
    }

    /**
     * Use one application from the subscription.
     */
    public function useApplication(): bool
    {
        if (!$this->hasRemainingApplications()) {
            return false;
        }

        $this->decrement('remaining_applications');
        return true;
    }

    /**
     * Reset applications for a new period.
     */
    public function resetApplications(): void
    {
        $this->update([
            'remaining_applications' => $this->subscriptionTier->included_job_applications,
            'last_reset_at' => now(),
        ]);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
