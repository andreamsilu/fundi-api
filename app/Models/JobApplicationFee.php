<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplicationFee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_id',
        'fundi_id',
        'fee_amount',
        'payment_type',
        'credit_transaction_id',
        'subscription_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fee_amount' => 'decimal:2',
    ];

    /**
     * Get the job that this fee is for.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the fundi who paid this fee.
     */
    public function fundi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fundi_id');
    }

    /**
     * Get the credit transaction associated with this fee.
     */
    public function creditTransaction(): BelongsTo
    {
        return $this->belongsTo(CreditTransaction::class);
    }

    /**
     * Get the subscription associated with this fee.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(FundiSubscription::class);
    }

    /**
     * Mark the fee as paid.
     */
    public function markAsPaid(): bool
    {
        return $this->update(['status' => 'paid']);
    }

    /**
     * Mark the fee as refunded.
     */
    public function markAsRefunded(): bool
    {
        return $this->update(['status' => 'refunded']);
    }

    /**
     * Scope a query to only include paid fees.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include pending fees.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
