<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceJob extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'location',
        'category_id',
        'status',
        'budget',
        'preferred_date',
        'images',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budget' => 'decimal:2',
        'preferred_date' => 'datetime',
        'images' => 'json',
        'status' => 'string',
    ];

    /**
     * Get the user that posted the job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that the job belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    /**
     * Get the booking for this job.
     */
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class, 'job_id');
    }

    /**
     * Get the review for this job.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class)->through('booking');
    }

    /**
     * Scope a query to only include open jobs.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include booked jobs.
     */
    public function scopeBooked($query)
    {
        return $query->where('status', 'booked');
    }

    /**
     * Scope a query to only include completed jobs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if the job is open for booking.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if the job is booked.
     */
    public function isBooked(): bool
    {
        return $this->status === 'booked';
    }

    /**
     * Check if the job is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the job is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
} 