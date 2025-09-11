<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingReview extends Model
{
    protected $table = 'ratings_reviews';
    
    protected $fillable = [
        'fundi_id',
        'customer_id',
        'job_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the fundi being rated.
     */
    public function fundi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fundi_id');
    }

    /**
     * Get the customer giving the rating.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the job associated with the rating.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
