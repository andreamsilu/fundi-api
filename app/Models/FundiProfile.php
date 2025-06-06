<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundiProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'skills',
        'rating',
        'location',
        'bio',
        'availability',
        'is_verified',
        'is_available',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'decimal:2',
        'availability' => 'json',
        'is_verified' => 'boolean',
        'is_available' => 'boolean',
    ];

    /**
     * Get the user that owns the fundi profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that the fundi belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    /**
     * Get the bookings for this fundi.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'fundi_id', 'user_id');
    }

    /**
     * Get the reviews for this fundi.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'fundi_id', 'user_id');
    }

    /**
     * Update the fundi's rating based on their reviews.
     */
    public function updateRating(): void
    {
        $this->rating = $this->reviews()->avg('rating') ?? 0;
        $this->save();
    }

    /**
     * Get the completed jobs count for this fundi.
     */
    public function getCompletedJobsCountAttribute(): int
    {
        return $this->bookings()
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Get the active bookings count for this fundi.
     */
    public function getActiveBookingsCountAttribute(): int
    {
        return $this->bookings()
            ->whereIn('status', ['pending', 'accepted'])
            ->count();
    }
} 