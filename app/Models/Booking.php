<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
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
        'customer_id',
        'service_job_id',
        'description',
        'scheduled_date',
        'scheduled_time',
        'location',
        'notes',
        'estimated_duration',
        'actual_duration',
        'estimated_cost',
        'actual_cost',
        'payment_status',
        'payment_method',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    /**
     * Get the job that this booking is for.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the fundi who accepted this booking.
     */
    public function fundi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fundi_id');
    }

    /**
     * Get the customer who created this booking.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the service job that this booking is for.
     */
    public function serviceJob(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'service_job_id');
    }

    /**
     * Get the review for this booking.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Scope a query to only include bookings with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include bookings for a specific fundi.
     */
    public function scopeForFundi($query, $fundiId)
    {
        return $query->where('fundi_id', $fundiId);
    }

    /**
     * Scope a query to only include bookings for a specific job.
     */
    public function scopeForJob($query, $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Check if the booking is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the booking is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if the booking is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the booking is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    /**
     * Check if the booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Accept the booking.
     */
    public function accept(): void
    {
        $this->update([
            'status' => 'accepted',
        ]);

        $this->job->update(['status' => 'booked']);
    }

    /**
     * Complete the booking.
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
        ]);

        $this->job->update(['status' => 'completed']);
    }

    /**
     * Decline the booking.
     */
    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }

    /**
     * Cancel the booking.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
        $this->job->update(['status' => 'open']);
    }
} 