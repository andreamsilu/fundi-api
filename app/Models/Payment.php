<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_provider',
        'payment_provider_id',
        'payment_provider_status',
        'payment_provider_response',
        'metadata',
        'payable_type',
        'payable_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'payment_provider_response' => 'array',
    ];

    /**
     * Get the user that owns the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payable model (booking or job).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark the payment as completed.
     */
    public function markAsCompleted(array $providerResponse = []): bool
    {
        return $this->update([
            'status' => 'completed',
            'payment_provider_status' => 'succeeded',
            'payment_provider_response' => $providerResponse,
        ]);
    }

    /**
     * Mark the payment as failed.
     */
    public function markAsFailed(array $providerResponse = []): bool
    {
        return $this->update([
            'status' => 'failed',
            'payment_provider_status' => 'failed',
            'payment_provider_response' => $providerResponse,
        ]);
    }

    /**
     * Get the payment amount in the smallest currency unit (e.g., cents).
     */
    public function getAmountInSmallestUnit(): int
    {
        return (int) ($this->amount * 100);
    }
} 