<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PremiumJobBooster extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_id',
        'user_id',
        'boost_type',
        'boost_fee',
        'business_model',
        'starts_at',
        'expires_at',
        'status',
        'payment_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'boost_fee' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the job that this booster is for.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the user who paid for this booster.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment associated with this booster.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Check if the booster is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->starts_at->isPast() && 
               $this->expires_at->isFuture();
    }

    /**
     * Check if the booster has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Scope a query to only include active boosters.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired boosters.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query by business model.
     */
    public function scopeByBusinessModel($query, string $businessModel)
    {
        return $query->where('business_model', $businessModel);
    }
}
