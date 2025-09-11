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
    ];

    protected $casts = [
        'duration_hours' => 'integer',
        'budget' => 'decimal:2',
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
}
