<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    protected $table = 'job_postings';
    
    protected $fillable = [
        'customer_id',
        'category_id',
        'title',
        'description',
        'budget',
        'deadline',
        'location',
        'location_lat',
        'location_lng',
        'urgency',
        'preferred_time',
        'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
        'deadline' => 'datetime',
    ];

    /**
     * Get the customer that owns the job.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the category that the job belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the applications for the job.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Get the media for the job.
     */
    public function media(): HasMany
    {
        return $this->hasMany(JobMedia::class);
    }
}
