<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'user_id',
        'fundi_id',
        'rating',
        'comment',
        'images',
        'is_verified',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'images' => 'json',
        'is_verified' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'is_verified',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            // Update fundi's rating when a new review is created
            $review->fundi->fundiProfile->updateRating();
        });

        static::updated(function ($review) {
            // Update fundi's rating when a review is updated
            $review->fundi->fundiProfile->updateRating();
        });

        static::deleted(function ($review) {
            // Update fundi's rating when a review is deleted
            $review->fundi->fundiProfile->updateRating();
        });
    }

    /**
     * Get the booking that was reviewed.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who wrote the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the fundi who was reviewed.
     */
    public function fundi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fundi_id');
    }

    /**
     * Scope a query to only include verified reviews.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to only include reviews with a minimum rating.
     */
    public function scopeMinRating($query, $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    /**
     * Verify the review.
     */
    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    /**
     * Unverify the review.
     */
    public function unverify(): void
    {
        $this->update(['is_verified' => false]);
    }
} 