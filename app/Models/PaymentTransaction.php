<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_plan_id',
        'transaction_type',
        'reference_id',
        'amount',
        'currency',
        'payment_method',
        'payment_reference',
        'status',
        'description',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment plan for this transaction
     */
    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if transaction is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->paid_at = now();
        $this->save();
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(): void
    {
        $this->status = 'failed';
        $this->save();
    }

    /**
     * Mark transaction as refunded
     */
    public function markAsRefunded(): void
    {
        $this->status = 'refunded';
        $this->save();
    }
}
