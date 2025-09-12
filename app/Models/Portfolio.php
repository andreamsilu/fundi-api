<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    protected $table = 'portfolio';
    
    protected $fillable = [
        'fundi_id',
        'title',
        'description',
        'skills_used',
        'duration_hours',
        'budget',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'is_visible',
    ];

    protected $casts = [
        'duration_hours' => 'integer',
        'budget' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the fundi that owns the portfolio item.
     */
    public function fundi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fundi_id');
    }

    /**
     * Get the media for the portfolio item.
     */
    public function media(): HasMany
    {
        return $this->hasMany(PortfolioMedia::class);
    }

    /**
     * Get the user who approved this portfolio item.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if portfolio item is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if portfolio item is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if portfolio item is pending approval
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if portfolio item is visible to customers
     */
    public function isVisible(): bool
    {
        return $this->is_visible && $this->isApproved();
    }

    /**
     * Approve the portfolio item
     */
    public function approve(User $approver): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'is_visible' => true,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the portfolio item
     */
    public function reject(User $rejector, string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'approved_by' => $rejector->id,
            'approved_at' => now(),
            'is_visible' => false,
            'rejection_reason' => $reason,
        ]);
    }
}
