<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueTracking extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'revenue_tracking';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'revenue_type',
        'user_id',
        'job_id',
        'business_model',
        'amount',
        'currency',
        'description',
        'payment_id',
        'credit_transaction_id',
        'subscription_id',
        'booster_id',
        'revenue_date',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'revenue_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the user associated with this revenue.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the job associated with this revenue.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the payment associated with this revenue.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the credit transaction associated with this revenue.
     */
    public function creditTransaction(): BelongsTo
    {
        return $this->belongsTo(CreditTransaction::class);
    }

    /**
     * Get the subscription associated with this revenue.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(FundiSubscription::class);
    }

    /**
     * Get the booster associated with this revenue.
     */
    public function booster(): BelongsTo
    {
        return $this->belongsTo(PremiumJobBooster::class, 'booster_id');
    }

    /**
     * Scope a query by revenue type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('revenue_type', $type);
    }

    /**
     * Scope a query by business model.
     */
    public function scopeByBusinessModel($query, string $businessModel)
    {
        return $query->where('business_model', $businessModel);
    }

    /**
     * Scope a query by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('revenue_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
